import { read, utils, type WorkBook } from 'xlsx';
import { reactive, computed } from 'vue';

/* ─────────────── Tipos ─────────────── */

/** Error de validación asociado a una celda específica. */
export interface CellError {
    fila: number;       // índice 0-based de la fila en la tabla de partidas
    columna: string;    // 'no' | 'descripcion' | 'unidad' | 'cantidad' | 'precio_unitario'
    mensaje: string;
}

export interface PartidaParsed {
    no: string;
    descripcion: string;
    unidad: string;
    cantidad: string;
    precioUnitario: string;
    esPadre: boolean;
    errores: CellError[];
}

export interface EncabezadoParsed {
    fecha: string;
    para: string;
    cliente: string;
    direccion: string;
    proveedor: string;
    vendedor: string;
    correoVendedor: string;
    obra: string;
}

export interface ExcelParseResult {
    encabezado: EncabezadoParsed;
    partidas: PartidaParsed[];
    erroresGlobales: string[];
}

/* ─────────────── Helpers de normalización ─────────────── */

function normalizarTexto(texto: string): string {
    return texto
        .toLowerCase()
        .trim()
        .replace(/á/g, 'a')
        .replace(/é/g, 'e')
        .replace(/í/g, 'i')
        .replace(/ó/g, 'o')
        .replace(/ú/g, 'u')
        .replace(/ñ/g, 'n');
}

function str(valor: unknown): string {
    if (valor === null || valor === undefined) return '';
    return String(valor).trim();
}

/* ─────────────── Parseo del Excel ─────────────── */

function leerEncabezado(filas: unknown[][], inicioTabla: number | null): EncabezadoParsed {
    const mapa: EncabezadoParsed = {
        fecha: '', para: '', cliente: '', direccion: '',
        proveedor: '', vendedor: '', correoVendedor: '', obra: '',
    };

    const limite = inicioTabla ?? filas.length;

    for (let i = 0; i < limite; i++) {
        const fila = filas[i];
        if (!fila) continue;
        const etiqueta = normalizarTexto(str(fila[0]));
        const valor = str(fila[1]);
        if (!etiqueta || !valor) continue;

        if (etiqueta.startsWith('fecha')) mapa.fecha = valor;
        else if (etiqueta.startsWith('para')) mapa.para = valor;
        else if (etiqueta.startsWith('cliente')) mapa.cliente = valor;
        else if (etiqueta.startsWith('direcci')) mapa.direccion = valor;
        else if (etiqueta.startsWith('proveedor')) mapa.proveedor = valor;
        else if (etiqueta.startsWith('correo vendedor') || etiqueta.startsWith('correo del vendedor'))
            mapa.correoVendedor = valor;
        else if (etiqueta.startsWith('vendedor')) mapa.vendedor = valor;
        else if (etiqueta.startsWith('obra')) mapa.obra = valor;
    }

    return mapa;
}

function localizarEncabezadoTabla(filas: unknown[][]): number | null {
    for (let i = 0; i < filas.length; i++) {
        const celda = str(filas[i]?.[0]).toLowerCase();
        if (celda === 'no.') return i;
    }
    return null;
}

function localizarFinTabla(filas: unknown[][], inicioTabla: number): number {
    for (let i = inicioTabla + 1; i < filas.length; i++) {
        const fila = filas[i];
        if (!fila) continue;

        // Detectar fila de condiciones de entrega
        let hits = 0;
        for (const celda of fila) {
            const n = normalizarTexto(str(celda));
            if (n.includes('tiempo de entrega') || n.includes('dias de credito') || n.includes('vigencia cotizacion'))
                hits++;
        }
        if (hits >= 2) return i;

        const primera = normalizarTexto(str(fila[0]));
        if (primera === 'nota:' || primera === 'notas:') return i;
    }
    return filas.length;
}

/* ─────────────── Validación de partidas ─────────────── */

/**
 * Valida si un valor de "No." es un entero válido (partida padre).
 * Acepta: "1", "2", "10", etc. Rechaza: "1.0", "abc", "", "0".
 */
function esNumeroEnteroValido(valor: string): boolean {
    return /^[1-9]\d*$/.test(valor);
}

/**
 * Valida si un valor de "No." es un formato decimal válido (subpartida).
 * Acepta: "1.1", "2.3", "10.15". Rechaza: "1.", ".1", "1.0", "abc".
 */
function esFormatoDecimalValido(valor: string): boolean {
    return /^[1-9]\d*\.[1-9]\d*$/.test(valor);
}

/**
 * Valida si un string representa un número positivo.
 */
function esNumericoPositivo(valor: string): boolean {
    if (!valor) return false;
    const num = Number(valor);
    return !isNaN(num) && num > 0;
}

/**
 * Valida si un string representa un número ≥ 0.
 */
function esNumericoNoNegativo(valor: string): boolean {
    if (!valor) return false;
    const num = Number(valor);
    return !isNaN(num) && num >= 0;
}

function validarPartidas(
    filas: unknown[][],
    inicioTabla: number,
    finTabla: number,
): { partidas: PartidaParsed[]; erroresGlobales: string[] } {
    const partidas: PartidaParsed[] = [];
    const erroresGlobales: string[] = [];
    const padresDefinidos = new Set<number>();
    let ultimoPadre: number | null = null;
    let ultimaHija: number | null = null;

    for (let i = inicioTabla + 1; i < finTabla; i++) {
        const fila = filas[i];
        if (!fila) continue;

        const no = str(fila[0]);
        const descripcion = str(fila[1]);
        const unidad = str(fila[2]);
        const cantidad = str(fila[3]);
        const precioUnitario = str(fila[4]);

        // Fila completamente vacía — saltar
        if (!no && !descripcion && !unidad && !cantidad && !precioUnitario) continue;

        const errores: CellError[] = [];
        const filaIdx = partidas.length;  // índice dentro del array de partidas parseadas
        const filaExcel = i + 1;          // número de fila en el Excel (1-based, para mensajes)

        const tieneDecimal = no.includes('.');
        const esPadre = !tieneDecimal;

        // ─── Validación de "No." ───
        if (!no) {
            errores.push({
                fila: filaIdx,
                columna: 'no',
                mensaje: `Fila ${filaExcel}: El número de partida es obligatorio.`,
            });
        } else if (esPadre) {
            // Debe ser entero positivo
            if (!esNumeroEnteroValido(no)) {
                errores.push({
                    fila: filaIdx,
                    columna: 'no',
                    mensaje: `Debe ser un entero positivo (ej. 1, 2, 3). Se encontró "${no}".`,
                });
            } else {
                const numPadre = parseInt(no, 10);

                if (numPadre > 65535) {
                    errores.push({
                        fila: filaIdx,
                        columna: 'no',
                        mensaje: `El número de sección no puede ser mayor a 65535. Se encontró ${numPadre}.`,
                    });
                }

                // Verificar secuencia de padres
                if (ultimoPadre !== null && numPadre !== ultimoPadre + 1) {
                    errores.push({
                        fila: filaIdx,
                        columna: 'no',
                        mensaje: `La numeración debe ser consecutiva. Se esperaba ${ultimoPadre + 1} pero se encontró ${numPadre}.`,
                    });
                }

                padresDefinidos.add(numPadre);
                ultimoPadre = numPadre;
                ultimaHija = null;  // reset hijas
            }
        } else {
            // Subpartida — debe tener formato "N.M"
            if (!esFormatoDecimalValido(no)) {
                errores.push({
                    fila: filaIdx,
                    columna: 'no',
                    mensaje: `Formato inválido. Las subpartidas deben usar el formato "N.M" (ej. 1.1, 2.3). Se encontró "${no}".`,
                });
            } else {
                const [numPadreStr, numHijaStr] = no.split('.');
                const numPadre = parseInt(numPadreStr, 10);
                const numHija = parseInt(numHijaStr, 10);

                if (numHija > 65535) {
                    errores.push({
                        fila: filaIdx,
                        columna: 'no',
                        mensaje: `El número de subpartida no puede ser mayor a 65535. Se encontró ${numHija}.`,
                    });
                }

                // ¿Existe la partida padre?
                if (!padresDefinidos.has(numPadre)) {
                    errores.push({
                        fila: filaIdx,
                        columna: 'no',
                        mensaje: `La sección ${numPadre} no ha sido definida antes de esta subpartida.`,
                    });
                }

                // ¿La hija es consecutiva?
                if (ultimoPadre === numPadre) {
                    const esperada = (ultimaHija ?? 0) + 1;
                    if (numHija !== esperada) {
                        errores.push({
                            fila: filaIdx,
                            columna: 'no',
                            mensaje: `La numeración de subpartida debe ser consecutiva. Se esperaba ${numPadre}.${esperada} pero se encontró ${no}.`,
                        });
                    }
                    ultimaHija = numHija;
                }
            }
        }

        // ─── Validación de "Descripción" ───
        if (!descripcion) {
            errores.push({
                fila: filaIdx,
                columna: 'descripcion',
                mensaje: 'La descripción es obligatoria.',
            });
        }

        // Las partidas padre solo necesitan No. y Descripción, no necesitan unidad/cantidad/precio
        if (!esPadre) {
            // ─── Validación de "Cantidad" ───
            if (!cantidad) {
                errores.push({
                    fila: filaIdx,
                    columna: 'cantidad',
                    mensaje: 'La cantidad es obligatoria para subpartidas.',
                });
            } else if (!esNumericoPositivo(cantidad)) {
                errores.push({
                    fila: filaIdx,
                    columna: 'cantidad',
                    mensaje: `Debe ser un número mayor que 0. Se encontró "${cantidad}".`,
                });
            }

            // ─── Validación de "Precio Unitario" ───
            if (!precioUnitario) {
                errores.push({
                    fila: filaIdx,
                    columna: 'precio_unitario',
                    mensaje: 'El precio unitario es obligatorio para subpartidas.',
                });
            } else if (!esNumericoNoNegativo(precioUnitario)) {
                errores.push({
                    fila: filaIdx,
                    columna: 'precio_unitario',
                    mensaje: `Debe ser un número ≥ 0. Se encontró "${precioUnitario}".`,
                });
            }

            // ─── Validación de "Unidad" (opcional pero con longitud máxima) ───
            if (unidad && unidad.length > 50) {
                errores.push({
                    fila: filaIdx,
                    columna: 'unidad',
                    mensaje: `Máximo 50 caracteres. Se encontraron ${unidad.length}.`,
                });
            }
        }

        partidas.push({
            no,
            descripcion,
            unidad,
            cantidad,
            precioUnitario,
            esPadre,
            errores,
        });
    }

    if (partidas.length === 0) {
        erroresGlobales.push('No se encontraron partidas en la tabla. Verifica que las filas estén debajo del encabezado "No.".');
    }

    return { partidas, erroresGlobales };
}

/* ─────────────── Composable principal ─────────────── */

export function useExcelCotizacionValidator() {
    const state = reactive({
        resultado: null as ExcelParseResult | null,
        cargando: false,
        archivo: null as File | null,
        errorLectura: '',
    });

    const tieneErrores = computed(() => {
        if (!state.resultado) return false;
        if (state.resultado.erroresGlobales.length > 0) return true;
        return state.resultado.partidas.some(p => p.errores.length > 0);
    });

    const totalErrores = computed(() => {
        if (!state.resultado) return 0;
        return (
            state.resultado.erroresGlobales.length +
            state.resultado.partidas.reduce((sum, p) => sum + p.errores.length, 0)
        );
    });

    async function parsearArchivo(file: File): Promise<void> {
        state.cargando = true;
        state.errorLectura = '';
        state.archivo = file;
        state.resultado = null;

        try {
            const buffer = await file.arrayBuffer();
            const workbook: WorkBook = read(buffer, { type: 'array' });
            const sheet = workbook.Sheets[workbook.SheetNames[0]];
            if (!sheet) {
                state.errorLectura = 'El archivo no contiene hojas de cálculo.';
                return;
            }

            // Leer como array de arrays, limitando columnas A-F
            const filas: unknown[][] = utils.sheet_to_json(sheet, {
                header: 1,
                range: 0,
                defval: '',
            });

            // Limitar a las primeras 6 columnas (A-F) igual que el backend
            const filasLimitadas = filas.map(fila =>
                Array.isArray(fila) ? fila.slice(0, 6) : [],
            );

            const inicioTabla = localizarEncabezadoTabla(filasLimitadas);
            const encabezado = leerEncabezado(filasLimitadas, inicioTabla);

            if (inicioTabla === null) {
                state.resultado = {
                    encabezado,
                    partidas: [],
                    erroresGlobales: [
                        'No se encontró el encabezado de tabla ("No.") en el archivo. Verifica que la primera columna de la fila de encabezados contenga exactamente "No.".',
                    ],
                };
                return;
            }

            const finTabla = localizarFinTabla(filasLimitadas, inicioTabla);
            const { partidas, erroresGlobales } = validarPartidas(filasLimitadas, inicioTabla, finTabla);

            state.resultado = { encabezado, partidas, erroresGlobales };
        } catch (e: unknown) {
            state.errorLectura = e instanceof Error
                ? `Error al leer el archivo: ${e.message}`
                : 'Error desconocido al leer el archivo.';
        } finally {
            state.cargando = false;
        }
    }

    function reset(): void {
        state.resultado = null;
        state.cargando = false;
        state.archivo = null;
        state.errorLectura = '';
    }

    return {
        state,
        tieneErrores,
        totalErrores,
        parsearArchivo,
        reset,
    };
}
