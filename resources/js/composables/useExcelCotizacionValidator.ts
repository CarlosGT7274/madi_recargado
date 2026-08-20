import { read, utils, type WorkBook } from 'xlsx';
import { reactive, computed } from 'vue';

/* ─────────────── Tipos ─────────────── */

/** Error de validación asociado a una celda específica. */
export interface CellError {
    fila: number;       // índice 0-based de la fila en la tabla de partidas
    columna: string;    // 'no' | 'descripcion' | 'unidad' | 'cantidad' | 'precio_unitario'
    mensaje: string;
}

/** Error asociado a un campo del encabezado (Fecha, Cliente, Obra, etc). */
export interface HeaderFieldError {
    campo: keyof EncabezadoParsed;
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
    /** Fecha ya normalizada a "d/m/Y" si se pudo determinar sin ambigüedad; null si es inválida/vacía. */
    fechaNormalizada: string | null;
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
    erroresEncabezado: HeaderFieldError[];
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

/**
 * Formatea un objeto Date a "d/m/Y" (el formato que el backend
 * CotizacionExcelImport::parsearFecha entiende de primera mano).
 */
function formatearFechaISO(fecha: Date): string {
    const dia = String(fecha.getDate()).padStart(2, '0');
    const mes = String(fecha.getMonth() + 1).padStart(2, '0');
    const anio = fecha.getFullYear();
    return `${dia}/${mes}/${anio}`;
}

/**
 * Fallback SOLO para cuando la celda de fecha no es una fecha nativa de
 * Excel (por ejemplo, el usuario escribió el texto a mano en una celda
 * de texto plano). En ese caso no hay forma de eliminar la ambigüedad
 * día/mes de un string tipo "8/20/26" salvo por descarte: si un número
 * es > 12, forzosamente es el día (no existe el "mes 20"). Si ambos son
 * ≤ 12 la ambigüedad es real y se asume d/m/Y por convención del
 * sistema.
 */
function parsearFechaTexto(valor: string): Date | null {
    const conSlash = valor.match(/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})$/);
    const conGuionInverso = valor.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
    const conGuion = valor.match(/^(\d{1,2})-(\d{1,2})-(\d{4})$/);

    let dia: number, mes: number, anio: number;

    if (conGuionInverso) {
        anio = Number(conGuionInverso[1]);
        mes = Number(conGuionInverso[2]);
        dia = Number(conGuionInverso[3]);
    } else if (conSlash || conGuion) {
        const m = (conSlash ?? conGuion)!;
        const a = Number(m[1]);
        const b = Number(m[2]);
        anio = Number(m[3]);
        if (anio < 100) anio += anio < 70 ? 2000 : 1900;

        if (a > 12 && b <= 12) {
            // "a" no puede ser mes → es día. Formato d/m/Y.
            dia = a;
            mes = b;
        } else if (b > 12 && a <= 12) {
            // "b" no puede ser mes → es día. Formato m/d/Y (US).
            mes = a;
            dia = b;
        } else {
            // Ambiguo (ambos ≤ 12) o ambos inválidos: convención del sistema = día/mes/año.
            dia = a;
            mes = b;
        }
    } else {
        return null;
    }

    if (mes < 1 || mes > 12 || dia < 1 || dia > 31) return null;

    const fecha = new Date(anio, mes - 1, dia);
    if (fecha.getFullYear() !== anio || fecha.getMonth() !== mes - 1 || fecha.getDate() !== dia) {
        return null;
    }

    return fecha;
}

/**
 * Determina si el encabezado de fecha es válido y, de serlo, su forma
 * normalizada "d/m/Y". Prioriza `fechaNativa` (objeto Date que xlsx ya
 * extrajo directamente del serial interno de la celda vía
 * `cellDates: true`, sin ninguna ambigüedad de orden día/mes) sobre el
 * texto formateado. Solo cae al parseo de texto cuando la celda no es
 * una fecha nativa de Excel (texto libre).
 */
function resolverFecha(valorTexto: string, fechaNativa: Date | null): { valida: boolean; normalizada: string | null } {
    if (fechaNativa instanceof Date && !isNaN(fechaNativa.getTime())) {
        return { valida: true, normalizada: formatearFechaISO(fechaNativa) };
    }

    if (!valorTexto) {
        return { valida: false, normalizada: null };
    }

    const parseada = parsearFechaTexto(valorTexto);
    if (parseada) {
        return { valida: true, normalizada: formatearFechaISO(parseada) };
    }

    return { valida: false, normalizada: null };
}

/* ─────────────── Parseo del Excel ─────────────── */

/**
 * @param filas          Lectura formateada (raw:false) — texto tal como Excel lo muestra.
 * @param filasFechas     Lectura con cellDates:true, misma posición de filas/columnas — para
 *                         extraer el objeto Date real de la celda de fecha sin ambigüedad.
 */
function leerEncabezado(filas: unknown[][], filasFechas: unknown[][], inicioTabla: number | null): EncabezadoParsed {
    const mapa: EncabezadoParsed = {
        fecha: '', fechaNormalizada: null, para: '', cliente: '', direccion: '',
        proveedor: '', vendedor: '', correoVendedor: '', obra: '',
    };

    const limite = inicioTabla ?? filas.length;

    for (let i = 0; i < limite; i++) {
        const fila = filas[i];
        if (!fila) continue;
        const etiqueta = normalizarTexto(str(fila[0]));
        const valor = str(fila[1]);
        if (!etiqueta || !valor) continue;

        if (etiqueta.startsWith('fecha')) {
            mapa.fecha = valor;

            // Celda hermana en la lectura con cellDates: true. Si xlsx la
            // reconoció como fecha nativa de Excel, aquí llega como objeto
            // Date real (sin ambigüedad de día/mes) en vez del string.
            const valorNativo = filasFechas[i]?.[1];
            const { valida, normalizada } = resolverFecha(
                valor,
                valorNativo instanceof Date ? valorNativo : null,
            );
            mapa.fechaNormalizada = valida ? normalizada : null;
        }
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

/**
 * Replica las reglas mínimas que CotizacionExcelImport exige en el
 * backend antes de crear la Cotización: encabezado "No." presente
 * (se valida aparte) y al menos Cliente u Obra reconocibles. Si el
 * frontend no exige esto, puede marcar "válido" un archivo que el
 * backend rechazará de todos modos con ValidationException — dejando
 * al usuario confundido justo como pasó.
 */
function validarEncabezado(encabezado: EncabezadoParsed): HeaderFieldError[] {
    const errores: HeaderFieldError[] = [];

    if (!encabezado.cliente && !encabezado.obra) {
        errores.push({
            campo: 'cliente',
            mensaje: 'Falta "Cliente:" u "Obra:" en la columna A con su valor en la columna B. Al menos uno de los dos es obligatorio.',
        });
    }

    if (encabezado.fecha && encabezado.fechaNormalizada === null) {
        errores.push({
            campo: 'fecha',
            mensaje: `"${encabezado.fecha}" no se pudo interpretar como fecha. Verifica que la celda tenga un valor de fecha válido en Excel (día/mes/año).`,
        });
    }

    if (encabezado.correoVendedor && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(encabezado.correoVendedor)) {
        errores.push({
            campo: 'correoVendedor',
            mensaje: `"${encabezado.correoVendedor}" no es un correo válido.`,
        });
    }

    return errores;
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
        if (!state.resultado) return true; // sin resultado parseado nunca se considera válido
        if (state.resultado.erroresGlobales.length > 0) return true;
        if (state.resultado.erroresEncabezado.length > 0) return true;
        return state.resultado.partidas.some(p => p.errores.length > 0);
    });

    const totalErrores = computed(() => {
        if (!state.resultado) return 0;
        return (
            state.resultado.erroresGlobales.length +
            state.resultado.erroresEncabezado.length +
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

            // raw: false → xlsx aplica el formato de celda (numFmt) antes de
            // devolver el valor como string. Sin esto, fechas y montos con
            // formato de moneda llegan como el número/serial crudo interno
            // de Excel (ej. "46254.4172") en vez del texto que el usuario ve
            // al abrir el archivo (ej. "19/08/2026" o "18115.37").
            const filas: unknown[][] = utils.sheet_to_json(sheet, {
                header: 1,
                range: 0,
                defval: '',
                raw: false,
            });

            // Segunda lectura, esta vez con cellDates: true: si la celda es
            // una fecha nativa de Excel (serial numérico + numFmt de
            // fecha), xlsx la entrega como objeto Date real construido a
            // partir del serial — sin ninguna ambigüedad de "es 8/20 o
            // 20/8". Esto es lo que Excel YA sabe con certeza que es esa
            // celda; parsear el texto formateado (arriba) para adivinar el
            // orden día/mes es innecesario y propenso a error cuando el
            // locale de origen del archivo usa m/d/Y.
            const filasFechas: unknown[][] = utils.sheet_to_json(sheet, {
                header: 1,
                range: 0,
                defval: '',
                raw: true,
                cellDates: true,
            });

            // Limitar a las primeras 6 columnas (A-F) igual que el backend
            const filasLimitadas = filas.map(fila =>
                Array.isArray(fila) ? fila.slice(0, 6) : [],
            );
            const filasFechasLimitadas = filasFechas.map(fila =>
                Array.isArray(fila) ? fila.slice(0, 6) : [],
            );

            const inicioTabla = localizarEncabezadoTabla(filasLimitadas);
            const encabezado = leerEncabezado(filasLimitadas, filasFechasLimitadas, inicioTabla);

            if (inicioTabla === null) {
                state.resultado = {
                    encabezado,
                    erroresEncabezado: [],
                    partidas: [],
                    erroresGlobales: [
                        'No se encontró el encabezado de tabla ("No.") en el archivo. Verifica que la primera columna de la fila de encabezados contenga exactamente "No.".',
                    ],
                };
                return;
            }

            const erroresEncabezado = validarEncabezado(encabezado);
            // encabezado.fechaNormalizada (d/m/Y sin ambigüedad) es el valor
            // que debería viajar al backend en vez de encabezado.fecha —
            // ver nota en el punto de envío del formulario/subida.

            const finTabla = localizarFinTabla(filasLimitadas, inicioTabla);
            const { partidas, erroresGlobales } = validarPartidas(filasLimitadas, inicioTabla, finTabla);

            state.resultado = { encabezado, erroresEncabezado, partidas, erroresGlobales };
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
