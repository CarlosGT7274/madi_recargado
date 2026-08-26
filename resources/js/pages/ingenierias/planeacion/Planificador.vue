<script setup lang="ts">
import { Deferred, Head, Link, router } from '@inertiajs/vue3';
import {
    AlertCircle,
    AlertTriangle,
    Bell,
    Building2,
    CalendarClock,
    CalendarRange,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    Circle,
    Clock,
    FolderOpen,
    Pencil,
    Plus,
    RotateCcw,
    Save,
    Search,
    Send,
    Settings,
    Trash2,
    User as UserIcon,
    Users,
    X,
    XCircle,
} from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';
import PlaneacionController from '@/actions/App/Http/Controllers/Ingenierias/Planeacion/PlaneacionController';
import PageLayout from '@/components/PageLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { isoWeekInfo, toIso } from '@/lib/isoWeek';

type EstadoPlaneacion = 'borrador' | 'enviada' | 'aprobada' | 'rechazada';

interface ProyectoRef {
    id: number;
    nombre: string;
    folio: string;
}

interface PlantaRef {
    id: number;
    nombre: string;
}

interface EmpleadoRef {
    id: number;
    nombre: string;
}

interface PartidaRef {
    id: number;
    descripcion: string;
}

interface PlaneacionResumen {
    id: number;
    semana: number;
    anio: number;
    estado: EstadoPlaneacion;
    reportadaNomina: boolean;
    proyecto: ProyectoRef | null;
    planta: PlantaRef | null;
    residente: string | null;
    residenteId: number | null;
    aprobador: string | null;
    fechaInicio: string;
    fechaFin: string;
    horasProgramadas: number;
    incidenciasCount: number;
    empleados: EmpleadoRef[];
    partidas: PartidaRef[];
}

interface FiltroPlanta {
    id: number;
    nombre: string;
}

interface FiltroProyecto {
    id: number;
    nombre: string;
    planta_id: number;
}

interface FiltroResidente {
    id: number;
    nombre: string;
}

interface FiltroOpciones {
    plantas: FiltroPlanta[];
    proyectos: FiltroProyecto[];
    residentes: FiltroResidente[];
}

const props = defineProps<{
    puedeAprobar: boolean;
    puedeCrear: boolean;
    puedeConfigurarCorte: boolean;
    filtros?: FiltroOpciones;
    planeaciones?: PlaneacionResumen[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Programación', href: PlaneacionController.index() }],
    },
});

// ---------- Año y calendario ----------

const anioActual = ref(new Date().getFullYear());

function cambiarAnio(delta: number): void {
    anioActual.value += delta;
}

function irAHoy(): void {
    anioActual.value = new Date().getFullYear();
    seleccionarDia(toIso(new Date()));
}

const nombresMeses = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
];
const diasSemana = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];

interface CeldaMes {
    fecha: Date | null;
    iso: string | null;
}

function celdasDeMes(anio: number, mes: number): CeldaMes[] {
    const primerDia = new Date(anio, mes, 1);
    const inicioOffset = (primerDia.getDay() + 6) % 7;
    const totalDias = new Date(anio, mes + 1, 0).getDate();

    const celdas: CeldaMes[] = [];
    for (let i = 0; i < inicioOffset; i++) celdas.push({ fecha: null, iso: null });
    for (let d = 1; d <= totalDias; d++) {
        const fecha = new Date(anio, mes, d);
        celdas.push({ fecha, iso: toIso(fecha) });
    }
    return celdas;
}

const meses = computed(() =>
    nombresMeses.map((nombre, indice) => ({
        nombre,
        indice,
        celdas: celdasDeMes(anioActual.value, indice),
        primerDiaIso: toIso(new Date(anioActual.value, indice, 1)),
        ultimoDiaIso: toIso(new Date(anioActual.value, indice + 1, 0)),
    })),
);

// ---------- Filtros ----------

const filtroPlantaId = ref<number | null>(null);
const filtroProyectoId = ref<number | null>(null);
const filtroResidenteId = ref<number | null>(null);
const filtroEstados = ref<Set<EstadoPlaneacion>>(new Set());

watch(filtroPlantaId, () => {
    filtroProyectoId.value = null;
});

const proyectosDisponibles = computed<FiltroProyecto[]>(() => {
    const todos = props.filtros?.proyectos ?? [];
    if (filtroPlantaId.value === null) return todos;
    return todos.filter((p) => p.planta_id === filtroPlantaId.value);
});

function toggleEstado(estado: EstadoPlaneacion): void {
    const nuevo = new Set(filtroEstados.value);
    if (nuevo.has(estado)) {
        nuevo.delete(estado);
    } else {
        nuevo.add(estado);
    }
    filtroEstados.value = nuevo;
}

function limpiarFiltros(): void {
    filtroPlantaId.value = null;
    filtroProyectoId.value = null;
    filtroResidenteId.value = null;
    filtroEstados.value = new Set();
}

const hayFiltrosActivos = computed(
    () => filtroPlantaId.value !== null || filtroProyectoId.value !== null || filtroResidenteId.value !== null || filtroEstados.value.size > 0,
);

const planeacionesFiltradas = computed<PlaneacionResumen[]>(() => {
    return (props.planeaciones ?? []).filter((p) => {
        if (filtroPlantaId.value !== null && p.planta?.id !== filtroPlantaId.value) return false;
        if (filtroProyectoId.value !== null && p.proyecto?.id !== filtroProyectoId.value) return false;
        if (filtroResidenteId.value !== null && p.residenteId !== filtroResidenteId.value) return false;
        if (filtroEstados.value.size > 0 && !filtroEstados.value.has(p.estado)) return false;
        return true;
    });
});

// ---------- Agregación por día (carga + estados + incidencias) ----------

interface DiaAgregado {
    horas: number;
    estados: Set<EstadoPlaneacion>;
    incidencias: boolean;
}

function fechasDelRango(inicioIso: string, finIso: string): string[] {
    const resultado: string[] = [];
    const cursor = new Date(`${inicioIso}T00:00:00`);
    const finDate = new Date(`${finIso}T00:00:00`);

    while (cursor <= finDate) {
        resultado.push(toIso(cursor));
        cursor.setDate(cursor.getDate() + 1);
    }

    return resultado;
}

const mapaDias = computed<Map<string, DiaAgregado>>(() => {
    const mapa = new Map<string, DiaAgregado>();

    for (const p of planeacionesFiltradas.value) {
        const dias = fechasDelRango(p.fechaInicio, p.fechaFin);
        const horasPorDia = dias.length > 0 ? p.horasProgramadas / dias.length : 0;

        for (const iso of dias) {
            const actual = mapa.get(iso) ?? { horas: 0, estados: new Set<EstadoPlaneacion>(), incidencias: false };
            actual.horas += horasPorDia;
            actual.estados.add(p.estado);
            if (p.incidenciasCount > 0) actual.incidencias = true;
            mapa.set(iso, actual);
        }
    }

    return mapa;
});

const maxHorasDia = computed(() => {
    let max = 0;
    for (const dia of mapaDias.value.values()) {
        if (dia.horas > max) max = dia.horas;
    }
    return max;
});

function intensidadClase(iso: string | null): string {
    if (!iso) return '';
    const dia = mapaDias.value.get(iso);
    if (!dia || dia.horas <= 0 || maxHorasDia.value <= 0) return '';

    const ratio = dia.horas / maxHorasDia.value;
    if (ratio > 0.75) return 'bg-primary/70';
    if (ratio > 0.5) return 'bg-primary/45';
    if (ratio > 0.25) return 'bg-primary/25';
    return 'bg-primary/10';
}

function tieneIncidencia(iso: string | null): boolean {
    if (!iso) return false;
    return mapaDias.value.get(iso)?.incidencias ?? false;
}

const estadoLabel: Record<EstadoPlaneacion, string> = {
    borrador: 'Borrador',
    enviada: 'En revisión',
    aprobada: 'Aprobada',
    rechazada: 'Rechazada',
};

const puntoClase: Record<EstadoPlaneacion, string> = {
    borrador: 'bg-muted-foreground',
    enviada: 'bg-blue-500',
    aprobada: 'bg-emerald-500',
    rechazada: 'bg-red-500',
};

const estadoBadgeClass: Record<EstadoPlaneacion, string> = {
    borrador: 'bg-muted text-muted-foreground',
    enviada: 'bg-blue-500/10 text-blue-600',
    aprobada: 'bg-emerald-500/10 text-emerald-600',
    rechazada: 'bg-red-500/10 text-red-600',
};

// ---------- Selección de rango (arbitrario, cruza meses) ----------

const rangoInicio = ref<string | null>(null);
const rangoFin = ref<string | null>(null);

function seleccionarDia(iso: string): void {
    if (!rangoInicio.value || (rangoInicio.value && rangoFin.value)) {
        rangoInicio.value = iso;
        rangoFin.value = null;
        return;
    }

    if (iso >= rangoInicio.value) {
        rangoFin.value = iso;
    } else {
        rangoFin.value = rangoInicio.value;
        rangoInicio.value = iso;
    }
}

function seleccionarMesCompleto(mes: { primerDiaIso: string; ultimoDiaIso: string }): void {
    rangoInicio.value = mes.primerDiaIso;
    rangoFin.value = mes.ultimoDiaIso;
}

function limpiarSeleccion(): void {
    rangoInicio.value = null;
    rangoFin.value = null;
}

const finEfectivo = computed<string | null>(() => rangoFin.value ?? rangoInicio.value);

function diaEnRango(iso: string | null): boolean {
    if (!iso || !rangoInicio.value || !finEfectivo.value) return false;
    return iso >= rangoInicio.value && iso <= finEfectivo.value;
}

function esExtremoRango(iso: string | null): boolean {
    if (!iso || !rangoInicio.value) return false;
    return iso === rangoInicio.value || iso === finEfectivo.value;
}

// ---------- Planeaciones del rango seleccionado + resumen ----------

const planeacionesEnRango = computed<PlaneacionResumen[]>(() => {
    if (!rangoInicio.value || !finEfectivo.value) return [];

    const inicio = rangoInicio.value;
    const fin = finEfectivo.value;

    return planeacionesFiltradas.value
        .filter((p) => p.fechaInicio <= fin && p.fechaFin >= inicio)
        .sort((a, b) => a.fechaInicio.localeCompare(b.fechaInicio));
});

interface ResumenRango {
    total: number;
    porEstado: Record<EstadoPlaneacion, number>;
    horasTotal: number;
    incidenciasTotal: number;
    empleados: EmpleadoRef[];
    proyectos: { id: number; nombre: string }[];
    plantas: { id: number; nombre: string }[];
}

const resumenRango = computed<ResumenRango>(() => {
    const lista = planeacionesEnRango.value;
    const porEstado: Record<EstadoPlaneacion, number> = { borrador: 0, enviada: 0, aprobada: 0, rechazada: 0 };
    let horasTotal = 0;
    let incidenciasTotal = 0;
    const empleadosMapa = new Map<number, string>();
    const proyectosMapa = new Map<number, string>();
    const plantasMapa = new Map<number, string>();

    for (const p of lista) {
        porEstado[p.estado]++;
        horasTotal += p.horasProgramadas;
        incidenciasTotal += p.incidenciasCount;
        for (const e of p.empleados) empleadosMapa.set(e.id, e.nombre);
        if (p.proyecto) proyectosMapa.set(p.proyecto.id, p.proyecto.nombre);
        if (p.planta) plantasMapa.set(p.planta.id, p.planta.nombre);
    }

    return {
        total: lista.length,
        porEstado,
        horasTotal,
        incidenciasTotal,
        empleados: Array.from(empleadosMapa, ([id, nombre]) => ({ id, nombre })),
        proyectos: Array.from(proyectosMapa, ([id, nombre]) => ({ id, nombre })),
        plantas: Array.from(plantasMapa, ([id, nombre]) => ({ id, nombre })),
    };
});

function rangoLabel(): string {
    if (!rangoInicio.value) return '';
    if (!rangoFin.value || rangoFin.value === rangoInicio.value) return rangoInicio.value;
    return `${rangoInicio.value} — ${rangoFin.value}`;
}

// ---------- Crear planeación: header y por-día, con la semana preseleccionada ----------

function queryDeSemana(iso: string): { anio: number; semana: number } {
    const { anio, semana } = isoWeekInfo(new Date(`${iso}T00:00:00`));
    return { anio, semana };
}

const hrefNuevaPlaneacion = computed(() =>
    PlaneacionController.create({ query: queryDeSemana(rangoInicio.value ?? toIso(new Date())) }),
);

function hrefNuevaPlaneacionParaDia(iso: string) {
    return PlaneacionController.create({ query: queryDeSemana(iso) });
}

// =====================================================================
// Compromisos de entrega por responsable (varios por usuario, cada uno
// opcionalmente ligado a una planta o a un proyecto puntual).
// Flujo: lista de responsables -> lista de sus compromisos -> formulario
// (nuevo o editar uno existente).
// =====================================================================

interface UsuarioCorte {
    id: number;
    nombre: string;
    totalCompromisos: number;
    vencidos: number;
    /** ISO datetime del compromiso vigente más próximo (o el vencido más reciente si todos vencieron). null = sin compromisos. */
    proximaFecha: string | null;
}

interface CompromisoDetalle {
    id: number;
    /** ISO datetime */
    fecha: string;
    nota: string | null;
    vencido: boolean;
    planta: { id: number; nombre: string } | null;
    proyecto: { id: number; nombre: string; folio: string } | null;
}

interface CompromisoBorrador {
    /** null = compromiso nuevo (aún no existe en el backend) */
    id: number | null;
    plantaId: number | null;
    proyectoId: number | null;
    /** ISO date, ej '2026-09-02' */
    fecha: string | null;
    /** 'HH:mm' */
    hora: string;
    nota: string;
}

type FiltroCorte = 'todos' | 'con_corte' | 'sin_corte';

const modalCorteAbierto = ref(false);

// ---------- Columna 1: lista de responsables ----------

const cargandoCorte = ref(false);
const errorCargaCorte = ref<string | null>(null);
const usuariosCorte = ref<UsuarioCorte[]>([]);
const cargaCorteCompletada = ref(false);

async function cargarUsuariosCorte(): Promise<void> {
    cargandoCorte.value = true;
    errorCargaCorte.value = null;

    try {
        const respuesta = await fetch(PlaneacionController.usuariosCorte.url(), {
            headers: { Accept: 'application/json' },
        });

        if (!respuesta.ok) {
            throw new Error(`El servidor respondió ${respuesta.status}`);
        }

        usuariosCorte.value = (await respuesta.json()) as UsuarioCorte[];
        cargaCorteCompletada.value = true;
    } catch (e) {
        errorCargaCorte.value =
            e instanceof Error
                ? `No se pudo cargar la lista de responsables (${e.message}).`
                : 'No se pudo cargar la lista de responsables.';
    } finally {
        cargandoCorte.value = false;
    }
}

function reintentarCargaCorte(): void {
    void cargarUsuariosCorte();
}

onMounted(() => {
    void cargarUsuariosCorte();
});

watch(modalCorteAbierto, (abierto) => {
    if (abierto) {
        seleccionadoCorteId.value = null;
        compromisosUsuario.value = [];
        compromisoEnEdicion.value = null;
        void cargarUsuariosCorte();
    }
});

const busquedaCorte = ref('');
const filtroCorte = ref<FiltroCorte>('todos');

function tieneCorte(usuario: UsuarioCorte): boolean {
    return usuario.totalCompromisos > 0;
}

const usuariosCorteFiltrados = computed<UsuarioCorte[]>(() => {
    const texto = busquedaCorte.value.trim().toLowerCase();

    return usuariosCorte.value.filter((u) => {
        if (texto && !u.nombre.toLowerCase().includes(texto)) return false;
        if (filtroCorte.value === 'con_corte' && !tieneCorte(u)) return false;
        if (filtroCorte.value === 'sin_corte' && tieneCorte(u)) return false;
        return true;
    });
});

const totalConCorte = computed(() => usuariosCorte.value.filter(tieneCorte).length);
const totalSinCorte = computed(() => usuariosCorte.value.length - totalConCorte.value);

function formatCompromisoCorto(iso: string | null): string {
    if (!iso) return '—';
    const fecha = new Date(iso);
    const dia = fecha.toLocaleDateString('es-MX', { day: 'numeric', month: 'short' });
    const hora = fecha.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
    return `${dia}, ${hora}`;
}

function formatCompromisoLargo(iso: string | null): string {
    if (!iso) return '—';
    const fecha = new Date(iso);
    const dia = fecha.toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long' });
    const hora = fecha.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
    return `${dia} a las ${hora}`;
}

function estaVencido(iso: string | null): boolean {
    return iso !== null && new Date(iso).getTime() <= Date.now();
}

/**
 * Agrupa a los responsables por el día de su PRÓXIMO compromiso (no todos
 * sus compromisos: alguien puede tener varios, pero el calendario solo
 * marca el más próximo para no saturar la vista). Alimenta los
 * indicadores del calendario y la tarjeta del panel lateral.
 */
const mapaCompromisosPorDia = computed<Map<string, UsuarioCorte[]>>(() => {
    const mapa = new Map<string, UsuarioCorte[]>();
    for (const u of usuariosCorte.value) {
        if (!u.proximaFecha) continue;
        const iso = u.proximaFecha.split('T')[0];
        const lista = mapa.get(iso) ?? [];
        lista.push(u);
        mapa.set(iso, lista);
    }
    return mapa;
});

function compromisosDelDia(iso: string | null): UsuarioCorte[] {
    if (!iso) return [];
    return mapaCompromisosPorDia.value.get(iso) ?? [];
}

function tieneCompromisoDia(iso: string | null): boolean {
    return compromisosDelDia(iso).length > 0;
}

function tieneCompromisoVencidoDia(iso: string | null): boolean {
    return compromisosDelDia(iso).some((u) => estaVencido(u.proximaFecha));
}

const totalCompromisosVencidos = computed(() => usuariosCorte.value.reduce((total, u) => total + u.vencidos, 0));

const compromisosEnRango = computed<UsuarioCorte[]>(() => {
    if (!rangoInicio.value || !finEfectivo.value) return [];
    const inicio = rangoInicio.value;
    const fin = finEfectivo.value;
    return usuariosCorte.value.filter((u) => {
        if (!u.proximaFecha) return false;
        const iso = u.proximaFecha.split('T')[0];
        return iso >= inicio && iso <= fin;
    });
});

function cuentaRegresiva(iso: string | null): string {
    if (!iso) return 'Sin fecha compromiso';
    const destino = new Date(iso).getTime();
    const diffMs = destino - Date.now();
    if (diffMs <= 0) return 'Vencida';

    const dias = Math.floor(diffMs / (1000 * 60 * 60 * 24));
    const horas = Math.floor((diffMs / (1000 * 60 * 60)) % 24);
    if (dias > 0) return `Vence en ${dias} d ${horas} h`;
    const minutos = Math.floor((diffMs / (1000 * 60)) % 60);
    return `Vence en ${horas} h ${minutos} min`;
}

// ---------- Columna 2: compromisos del responsable seleccionado ----------

const seleccionadoCorteId = ref<number | null>(null);
const usuarioCorteSeleccionado = computed<UsuarioCorte | null>(
    () => usuariosCorte.value.find((u) => u.id === seleccionadoCorteId.value) ?? null,
);

const cargandoCompromisos = ref(false);
const errorCompromisos = ref<string | null>(null);
const compromisosUsuario = ref<CompromisoDetalle[]>([]);

async function cargarCompromisosDeUsuario(usuarioId: number): Promise<void> {
    cargandoCompromisos.value = true;
    errorCompromisos.value = null;

    try {
        const respuesta = await fetch(PlaneacionController.compromisosDeUsuario.url(usuarioId), {
            headers: { Accept: 'application/json' },
        });

        if (!respuesta.ok) {
            throw new Error(`El servidor respondió ${respuesta.status}`);
        }

        compromisosUsuario.value = (await respuesta.json()) as CompromisoDetalle[];
    } catch (e) {
        errorCompromisos.value =
            e instanceof Error
                ? `No se pudieron cargar sus compromisos (${e.message}).`
                : 'No se pudieron cargar sus compromisos.';
    } finally {
        cargandoCompromisos.value = false;
    }
}

function seleccionarUsuarioCorte(usuario: UsuarioCorte): void {
    seleccionadoCorteId.value = usuario.id;
    compromisoEnEdicion.value = null;
    void cargarCompromisosDeUsuario(usuario.id);
}

function volverALaListaCorte(): void {
    seleccionadoCorteId.value = null;
    compromisosUsuario.value = [];
    compromisoEnEdicion.value = null;
}

function alcanceLabel(c: CompromisoDetalle | CompromisoBorrador): string {
    if ('proyecto' in c) {
        if (c.proyecto) return c.proyecto.nombre;
        if (c.planta) return c.planta.nombre;
        return 'Compromiso general';
    }
    return 'Compromiso';
}

// ---------- Columna 3: formulario (nuevo o edición de un compromiso) ----------

const compromisoEnEdicion = ref<CompromisoBorrador | null>(null);
const guardandoCorte = ref(false);
const errorGuardadoCorte = ref<string | null>(null);
const confirmandoEliminar = ref<number | null>(null);

function plantaOpciones(): FiltroPlanta[] {
    return props.filtros?.plantas ?? [];
}

function proyectoOpcionesPara(plantaId: number | null): FiltroProyecto[] {
    const todos = props.filtros?.proyectos ?? [];
    if (plantaId === null) return [];
    return todos.filter((p) => p.planta_id === plantaId);
}

function iniciarNuevoCompromiso(): void {
    const hoy = new Date();
    mesCompromisoActual.value = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    compromisoEnEdicion.value = {
        id: null,
        plantaId: null,
        proyectoId: null,
        fecha: null,
        hora: '17:00',
        nota: '',
    };
    errorGuardadoCorte.value = null;
}

function editarCompromiso(c: CompromisoDetalle): void {
    const fecha = new Date(c.fecha);
    mesCompromisoActual.value = new Date(fecha.getFullYear(), fecha.getMonth(), 1);
    compromisoEnEdicion.value = {
        id: c.id,
        plantaId: c.planta?.id ?? null,
        proyectoId: c.proyecto?.id ?? null,
        fecha: c.fecha.split('T')[0],
        hora: c.fecha.split('T')[1]?.slice(0, 5) ?? '17:00',
        nota: c.nota ?? '',
    };
    errorGuardadoCorte.value = null;
}

function cancelarEdicionCompromiso(): void {
    compromisoEnEdicion.value = null;
    errorGuardadoCorte.value = null;
}

watch(
    () => compromisoEnEdicion.value?.plantaId ?? null,
    () => {
        if (!compromisoEnEdicion.value) return;
        const opciones = proyectoOpcionesPara(compromisoEnEdicion.value.plantaId);
        if (compromisoEnEdicion.value.proyectoId !== null && !opciones.some((p) => p.id === compromisoEnEdicion.value?.proyectoId)) {
            compromisoEnEdicion.value.proyectoId = null;
        }
    },
);

const erroresValidacionCorte = computed<string[]>(() => {
    if (!compromisoEnEdicion.value) return [];
    const errores: string[] = [];

    if (!compromisoEnEdicion.value.fecha) {
        errores.push('Elige una fecha.');
    }
    if (compromisoEnEdicion.value.nota.length > 500) {
        errores.push('La nota no puede superar 500 caracteres.');
    }

    return errores;
});

const compromisoEsValido = computed(() => erroresValidacionCorte.value.length === 0);

function guardarCompromiso(): void {
    if (!compromisoEnEdicion.value || !compromisoEsValido.value || !usuarioCorteSeleccionado.value) return;

    const b = compromisoEnEdicion.value;
    const usuarioId = usuarioCorteSeleccionado.value.id;
    guardandoCorte.value = true;
    errorGuardadoCorte.value = null;

    const payload = {
        planta_id: b.plantaId,
        proyecto_id: b.proyectoId,
        fecha: `${b.fecha}T${b.hora}`,
        nota: b.nota.trim() === '' ? null : b.nota.trim(),
    };

    const peticion =
        b.id === null
            ? router.post(PlaneacionController.storeCompromiso.url(usuarioId), payload, opcionesPeticionCompromiso(usuarioId))
            : router.put(PlaneacionController.actualizarCompromiso.url(b.id), payload, opcionesPeticionCompromiso(usuarioId));

    void peticion;
}

function opcionesPeticionCompromiso(usuarioId: number) {
    return {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            compromisoEnEdicion.value = null;
            void cargarCompromisosDeUsuario(usuarioId);
            void cargarUsuariosCorte();
        },
        onError: (errores: Record<string, string>) => {
            errorGuardadoCorte.value =
                Object.values(errores)[0] ?? 'No se pudo guardar el compromiso. Revisa los datos e intenta de nuevo.';
        },
        onFinish: () => {
            guardandoCorte.value = false;
        },
    };
}

function pedirConfirmacionEliminar(compromisoId: number): void {
    confirmandoEliminar.value = compromisoId;
}

function cancelarConfirmacionEliminar(): void {
    confirmandoEliminar.value = null;
}

function confirmarEliminarCompromiso(): void {
    if (confirmandoEliminar.value === null || !usuarioCorteSeleccionado.value) return;

    const compromisoId = confirmandoEliminar.value;
    const usuarioId = usuarioCorteSeleccionado.value.id;
    confirmandoEliminar.value = null;
    guardandoCorte.value = true;

    router.delete(PlaneacionController.destroyCompromiso.url(compromisoId), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            void cargarCompromisosDeUsuario(usuarioId);
            void cargarUsuariosCorte();
        },
        onFinish: () => {
            guardandoCorte.value = false;
        },
    });
}

// ---------- Mini-calendario para elegir fecha (navegable por mes) ----------

const mesCompromisoActual = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1));

function cambiarMesCompromiso(delta: number): void {
    const fecha = new Date(mesCompromisoActual.value);
    fecha.setMonth(fecha.getMonth() + delta);
    mesCompromisoActual.value = fecha;
}

const celdasMesCompromiso = computed<CeldaMes[]>(() =>
    celdasDeMes(mesCompromisoActual.value.getFullYear(), mesCompromisoActual.value.getMonth()),
);

const nombreMesCompromiso = computed(
    () => `${nombresMeses[mesCompromisoActual.value.getMonth()]} ${mesCompromisoActual.value.getFullYear()}`,
);

const hoyIso = toIso(new Date());

interface DiaCompromiso {
    iso: string | null;
    diaMes: number | null;
    esPasado: boolean;
    esHoy: boolean;
}

const diasCompromisoDisponibles = computed<DiaCompromiso[]>(() =>
    celdasMesCompromiso.value.map((celda) => ({
        iso: celda.iso,
        diaMes: celda.fecha ? celda.fecha.getDate() : null,
        esPasado: celda.iso !== null && celda.iso < hoyIso,
        esHoy: celda.iso === hoyIso,
    })),
);

function elegirDiaCompromiso(iso: string): void {
    if (!compromisoEnEdicion.value) return;
    compromisoEnEdicion.value.fecha = iso;
}

// ---------- Acciones sobre planeaciones ----------

function aprobar(id: number): void {
    router.post(PlaneacionController.aprobar(id).url, {}, { preserveScroll: true });
}

function rechazar(id: number): void {
    const comentarios = prompt('Motivo del rechazo:');
    if (!comentarios) return;
    router.post(PlaneacionController.rechazar(id).url, { comentarios }, { preserveScroll: true });
}

function reportarNomina(id: number): void {
    router.post(PlaneacionController.reportarNomina(id).url, {}, { preserveScroll: true });
}
</script>

<template>

    <Head title="Programación" />

    <PageLayout title="Programación"
        description="Overview anual de planeaciones: selecciona un rango para ver el detalle">
        <template #actions>
            <div class="flex items-center gap-2">
                <Button v-if="props.puedeConfigurarCorte" variant="outline" class="relative"
                    @click="modalCorteAbierto = true">
                    <Settings class="mr-2 size-4" />
                    Fechas compromiso
                    <span v-if="totalCompromisosVencidos > 0"
                        class="absolute -right-1.5 -top-1.5 flex size-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white"
                        :title="`${totalCompromisosVencidos} compromiso(s) vencido(s)`">
                        {{ totalCompromisosVencidos }}
                    </span>
                </Button>
                <Link v-if="props.puedeCrear" :href="hrefNuevaPlaneacion">
                    <Button>
                        <Plus class="mr-2 size-4" />
                        Nueva Planeación
                    </Button>
                </Link>
            </div>
        </template>

        <Deferred :data="['planeaciones', 'filtros']">
            <template #fallback>
                <div class="h-96 animate-pulse rounded-2xl border bg-card/50" />
            </template>

            <div v-if="planeaciones" class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center gap-1">
                        <button type="button" class="rounded-md p-1.5 hover:bg-accent" @click="cambiarAnio(-1)">
                            <ChevronLeft class="size-4" />
                        </button>
                        <p class="w-16 text-center text-lg font-semibold">{{ anioActual }}</p>
                        <button type="button" class="rounded-md p-1.5 hover:bg-accent" @click="cambiarAnio(1)">
                            <ChevronRight class="size-4" />
                        </button>
                        <Button variant="ghost" size="sm" class="ml-2" @click="irAHoy">Hoy</Button>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <Select :model-value="filtroPlantaId ? String(filtroPlantaId) : 'todas'"
                            @update:model-value="(v) => (filtroPlantaId = v === 'todas' ? null : Number(v))">
                            <SelectTrigger class="h-8 w-36 text-xs">
                                <SelectValue placeholder="Planta" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="todas">Todas las plantas</SelectItem>
                                <SelectItem v-for="p in filtros?.plantas ?? []" :key="p.id" :value="String(p.id)">
                                    {{ p.nombre }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <Select :model-value="filtroProyectoId ? String(filtroProyectoId) : 'todos'"
                            @update:model-value="(v) => (filtroProyectoId = v === 'todos' ? null : Number(v))">
                            <SelectTrigger class="h-8 w-40 text-xs">
                                <SelectValue placeholder="Proyecto" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="todos">Todos los proyectos</SelectItem>
                                <SelectItem v-for="p in proyectosDisponibles" :key="p.id" :value="String(p.id)">
                                    {{ p.nombre }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <Select :model-value="filtroResidenteId ? String(filtroResidenteId) : 'todos'"
                            @update:model-value="(v) => (filtroResidenteId = v === 'todos' ? null : Number(v))">
                            <SelectTrigger class="h-8 w-36 text-xs">
                                <SelectValue placeholder="Residente" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="todos">Todos</SelectItem>
                                <SelectItem v-for="r in filtros?.residentes ?? []" :key="r.id" :value="String(r.id)">
                                    {{ r.nombre }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <div class="flex items-center gap-1 rounded-lg border p-0.5">
                            <button v-for="estado in (Object.keys(estadoLabel) as EstadoPlaneacion[])" :key="estado"
                                type="button"
                                class="flex items-center gap-1 rounded-md px-2 py-1 text-[11px] font-medium transition-colors"
                                :class="filtroEstados.has(estado) ? estadoBadgeClass[estado] : 'text-muted-foreground hover:bg-accent'"
                                @click="toggleEstado(estado)">
                                <span class="size-1.5 rounded-full" :class="puntoClase[estado]" />
                                {{ estadoLabel[estado] }}
                            </button>
                        </div>

                        <Button v-if="hayFiltrosActivos" variant="ghost" size="sm" class="text-xs"
                            @click="limpiarFiltros">
                            <X class="mr-1 size-3.5" />
                            Limpiar filtros
                        </Button>
                    </div>
                </div>

                <div class="grid gap-4 xl:grid-cols-[1fr_380px]">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div v-for="mes in meses" :key="mes.indice" class="rounded-xl border bg-card p-3 shadow-sm">
                            <div class="mb-2 flex items-center justify-between">
                                <p class="text-sm font-semibold">{{ mes.nombre }}</p>
                                <button type="button"
                                    class="text-[10px] text-muted-foreground hover:text-primary hover:underline"
                                    @click="seleccionarMesCompleto(mes)">
                                    Ver mes
                                </button>
                            </div>

                            <div class="grid grid-cols-7 gap-0.5 text-center text-[10px] text-muted-foreground">
                                <span v-for="(d, i) in diasSemana" :key="`${mes.indice}-${d}-${i}`">{{ d }}</span>
                            </div>

                            <div class="mt-0.5 grid grid-cols-7 gap-0.5">
                                <div v-for="(celda, idx) in mes.celdas" :key="idx" class="group relative aspect-square"
                                    :class="!celda.fecha ? 'invisible' : ''">
                                    <button type="button" :disabled="!celda.fecha"
                                        class="flex size-full flex-col items-center justify-center rounded text-[11px] transition-colors disabled:cursor-default"
                                        :class="[
                                            celda.fecha && !diaEnRango(celda.iso) ? intensidadClase(celda.iso) || 'hover:bg-accent' : '',
                                            diaEnRango(celda.iso) && !esExtremoRango(celda.iso) ? 'bg-primary/20' : '',
                                            esExtremoRango(celda.iso) ? 'bg-primary text-primary-foreground' : '',
                                        ]" @click="celda.iso && seleccionarDia(celda.iso)">
                                        {{ celda.fecha?.getDate() }}
                                        <AlertTriangle v-if="tieneIncidencia(celda.iso)"
                                            class="absolute right-0 top-0 size-2.5 text-amber-500" />
                                        <CalendarClock v-if="tieneCompromisoDia(celda.iso)"
                                            class="absolute bottom-0 left-0 size-3.5"
                                            :class="tieneCompromisoVencidoDia(celda.iso) ? 'text-red-500' : 'text-purple-500'"
                                            :title="compromisosDelDia(celda.iso).map((u) => u.nombre).join(', ')" />
                                    </button>

                                    <Link v-if="props.puedeCrear && celda.iso"
                                        :href="hrefNuevaPlaneacionParaDia(celda.iso)"
                                        class="absolute -right-1 -top-1 hidden size-4 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-sm group-hover:flex"
                                        title="Nueva planeación para esta semana" @click.stop>
                                        <Plus class="size-2.5" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="xl:sticky xl:top-4 xl:self-start">
                        <div v-if="!rangoInicio"
                            class="rounded-2xl border border-dashed bg-card/50 p-6 text-center shadow-sm">
                            <CalendarRange class="mx-auto size-8 text-muted-foreground" />
                            <p class="mt-3 text-sm font-medium">Selecciona un rango</p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Haz clic en un día para empezar, y clic en otro día (aunque sea de otro mes) para cerrar
                                el rango.
                                También puedes usar "Ver mes" en cualquier tarjeta.
                            </p>
                        </div>

                        <div v-else class="space-y-4">
                            <div class="rounded-2xl border bg-card p-4 shadow-sm">
                                <div class="mb-3 flex items-center justify-between gap-2">
                                    <p class="text-sm font-semibold">{{ rangoLabel() }}</p>
                                    <div class="flex items-center gap-2">
                                        <Link v-if="props.puedeCrear" :href="hrefNuevaPlaneacion">
                                            <Button variant="outline" size="sm" class="h-7 text-xs">
                                                <Plus class="mr-1 size-3" />
                                                Nueva
                                            </Button>
                                        </Link>
                                        <button type="button" class="text-xs text-muted-foreground hover:underline"
                                            @click="limpiarSeleccion">
                                            Limpiar
                                        </button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2 text-center">
                                    <div class="rounded-lg bg-muted/40 p-2">
                                        <p class="text-lg font-bold">{{ resumenRango.total }}</p>
                                        <p class="text-[10px] uppercase text-muted-foreground">Planeaciones</p>
                                    </div>
                                    <div class="rounded-lg bg-muted/40 p-2">
                                        <p class="text-lg font-bold">{{ resumenRango.horasTotal.toFixed(0) }}h</p>
                                        <p class="text-[10px] uppercase text-muted-foreground">Horas programadas</p>
                                    </div>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    <Badge v-for="estado in (Object.keys(estadoLabel) as EstadoPlaneacion[])"
                                        v-show="resumenRango.porEstado[estado] > 0" :key="estado"
                                        :class="estadoBadgeClass[estado]" class="text-[10px]">
                                        {{ resumenRango.porEstado[estado] }} {{ estadoLabel[estado] }}
                                    </Badge>
                                    <Badge v-if="resumenRango.incidenciasTotal > 0"
                                        class="gap-1 bg-amber-500/10 text-[10px] text-amber-600">
                                        <AlertTriangle class="size-3" />
                                        {{ resumenRango.incidenciasTotal }} incidencias
                                    </Badge>
                                </div>

                                <div v-if="resumenRango.plantas.length"
                                    class="mt-3 flex items-start gap-1.5 text-xs text-muted-foreground">
                                    <Building2 class="mt-0.5 size-3.5 shrink-0" />
                                    <span>{{resumenRango.plantas.map((p) => p.nombre).join(', ')}}</span>
                                </div>
                                <div v-if="resumenRango.proyectos.length"
                                    class="mt-1.5 flex items-start gap-1.5 text-xs text-muted-foreground">
                                    <FolderOpen class="mt-0.5 size-3.5 shrink-0" />
                                    <span>{{resumenRango.proyectos.map((p) => p.nombre).join(', ')}}</span>
                                </div>
                                <div v-if="resumenRango.empleados.length"
                                    class="mt-1.5 flex items-start gap-1.5 text-xs text-muted-foreground">
                                    <Users class="mt-0.5 size-3.5 shrink-0" />
                                    <span>{{ resumenRango.empleados.length }} empleados involucrados</span>
                                </div>
                            </div>

                            <div v-if="compromisosEnRango.length" class="rounded-2xl border bg-card p-4 shadow-sm">
                                <p class="mb-2 flex items-center gap-1.5 text-sm font-semibold">
                                    <CalendarClock class="size-4 text-muted-foreground" />
                                    Compromisos de entrega
                                </p>
                                <div class="space-y-1.5">
                                    <div v-for="u in compromisosEnRango" :key="u.id"
                                        class="flex items-center justify-between gap-2 rounded-lg bg-muted/40 px-2.5 py-1.5 text-xs">
                                        <span class="font-medium">{{ u.nombre }}</span>
                                        <Badge
                                            :class="estaVencido(u.proximaFecha) ? 'bg-red-500/10 text-red-600' : 'bg-purple-500/10 text-purple-600'"
                                            class="text-[10px]">
                                            {{ formatCompromisoCorto(u.proximaFecha) }}
                                            <template v-if="u.totalCompromisos > 1"> (+{{ u.totalCompromisos - 1
                                            }})</template>
                                        </Badge>
                                    </div>
                                </div>
                            </div>

                            <div class="max-h-[60vh] space-y-2 overflow-y-auto pr-1">
                                <div v-if="!planeacionesEnRango.length"
                                    class="rounded-2xl border bg-card p-6 text-center text-sm text-muted-foreground shadow-sm">
                                    Sin planeaciones en este rango.
                                </div>

                                <div v-for="p in planeacionesEnRango" :key="p.id"
                                    class="rounded-xl border bg-card p-3 shadow-sm">
                                    <Link :href="PlaneacionController.show(p.id)" class="block">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="text-sm font-semibold">Semana {{ p.semana }}/{{ p.anio }}</p>
                                            <Badge :class="estadoBadgeClass[p.estado]" class="text-[10px] uppercase">
                                                {{ estadoLabel[p.estado] }}
                                            </Badge>
                                        </div>
                                        <p class="mt-1 text-xs text-muted-foreground">
                                            {{ p.proyecto?.nombre ?? '—' }} · {{ p.planta?.nombre ?? '—' }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">Residente: {{ p.residente ?? '—' }}</p>
                                    </Link>

                                    <div
                                        class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-muted-foreground">
                                        <span class="flex items-center gap-1">
                                            <Clock class="size-3" /> {{ p.horasProgramadas.toFixed(1) }}h
                                        </span>
                                        <span v-if="p.empleados.length" class="flex items-center gap-1">
                                            <Users class="size-3" /> {{ p.empleados.length }}
                                        </span>
                                        <span v-if="p.incidenciasCount > 0"
                                            class="flex items-center gap-1 text-amber-600">
                                            <AlertTriangle class="size-3" /> {{ p.incidenciasCount }}
                                        </span>
                                    </div>

                                    <div v-if="p.partidas.length" class="mt-1.5 flex flex-wrap gap-1">
                                        <span v-for="partida in p.partidas.slice(0, 3)" :key="partida.id"
                                            class="rounded-full bg-muted px-2 py-0.5 text-[10px] text-muted-foreground">
                                            {{ partida.descripcion }}
                                        </span>
                                        <span v-if="p.partidas.length > 3" class="text-[10px] text-muted-foreground">
                                            +{{ p.partidas.length - 3 }} más
                                        </span>
                                    </div>

                                    <Link v-if="p.puedeEditar" :href="PlaneacionController.show(p.id)">
                                        <Button size="sm" variant="outline" class="h-7 w-full text-xs" @click.stop>
                                            <Pencil class="mr-1 size-3" />
                                            Editar
                                        </Button>
                                    </Link>

                                    <div v-if="p.estado === 'enviada' && props.puedeAprobar" class="mt-2 flex gap-2">
                                        <Button size="sm"
                                            class="h-7 flex-1 bg-emerald-600 text-xs text-white hover:bg-emerald-700"
                                            @click.stop.prevent="aprobar(p.id)">
                                            <CheckCircle2 class="mr-1 size-3" />
                                            Aprobar
                                        </Button>
                                        <Button size="sm" variant="outline"
                                            class="h-7 flex-1 border-red-300 text-xs text-red-600 hover:bg-red-50"
                                            @click.stop.prevent="rechazar(p.id)">
                                            <XCircle class="mr-1 size-3" />
                                            Rechazar
                                        </Button>
                                    </div>
                                    <Button v-else-if="p.estado === 'aprobada' && !p.reportadaNomina" size="sm"
                                        variant="outline" class="mt-2 h-7 w-full text-xs"
                                        @click.stop.prevent="reportarNomina(p.id)">
                                        <Send class="mr-1 size-3" />
                                        Reportar a nómina
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Deferred>
    </PageLayout>

    <!-- Modal: compromisos de entrega (responsable -> sus compromisos -> formulario) -->
    <Dialog v-model:open="modalCorteAbierto">
        <DialogContent class="flex h-[85vh] max-h-[760px] flex-col overflow-hidden sm:max-w-5xl">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <CalendarClock class="size-5" />
                    Compromisos de entrega
                </DialogTitle>
                <DialogDescription>
                    Cada responsable puede tener varios compromisos: uno general, o ligados a una planta o a un
                    proyecto puntual. Se notifica automáticamente al crear, editar o quitar uno.
                </DialogDescription>
            </DialogHeader>

            <div v-if="cargandoCorte && !cargaCorteCompletada"
                class="flex flex-1 flex-col items-center justify-center gap-2 text-sm text-muted-foreground">
                <div class="size-6 animate-spin rounded-full border-2 border-muted-foreground/30 border-t-primary" />
                Cargando responsables…
            </div>

            <div v-else-if="errorCargaCorte"
                class="flex flex-1 flex-col items-center justify-center gap-3 px-6 text-center">
                <AlertCircle class="size-8 text-red-500" />
                <p class="text-sm font-medium">No se pudo cargar la lista</p>
                <p class="text-xs text-muted-foreground">{{ errorCargaCorte }}</p>
                <Button size="sm" variant="outline" @click="reintentarCargaCorte">
                    <RotateCcw class="mr-2 size-3.5" />
                    Reintentar
                </Button>
            </div>

            <div v-else-if="cargaCorteCompletada && usuariosCorte.length === 0"
                class="flex flex-1 flex-col items-center justify-center gap-2 px-6 text-center">
                <Users class="size-8 text-muted-foreground" />
                <p class="text-sm font-medium">No hay usuarios con permiso de crear planeaciones</p>
                <p class="text-xs text-muted-foreground">
                    Asigna el permiso de creación de planeaciones a al menos un usuario para poder configurarle
                    compromisos de entrega.
                </p>
            </div>

            <div v-else class="flex min-h-0 flex-1 gap-4">
                <!-- Columna 1: responsables -->
                <div class="flex w-full flex-col gap-2 border-r pr-3"
                    :class="usuarioCorteSeleccionado ? 'hidden sm:flex sm:w-52 sm:shrink-0' : ''">
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute left-2 top-1/2 size-3.5 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="busquedaCorte" placeholder="Buscar responsable…" class="h-8 pl-7 text-xs" />
                    </div>

                    <div class="flex items-center gap-1 rounded-lg border p-0.5 text-[11px]">
                        <button type="button" class="flex-1 rounded-md px-1.5 py-1 font-medium transition-colors"
                            :class="filtroCorte === 'todos' ? 'bg-accent' : 'text-muted-foreground hover:bg-accent/50'"
                            @click="filtroCorte = 'todos'">
                            Todos ({{ usuariosCorte.length }})
                        </button>
                        <button type="button" class="flex-1 rounded-md px-1.5 py-1 font-medium transition-colors"
                            :class="filtroCorte === 'con_corte' ? 'bg-emerald-500/10 text-emerald-600' : 'text-muted-foreground hover:bg-accent/50'"
                            @click="filtroCorte = 'con_corte'">
                            Con fecha ({{ totalConCorte }})
                        </button>
                        <button type="button" class="flex-1 rounded-md px-1.5 py-1 font-medium transition-colors"
                            :class="filtroCorte === 'sin_corte' ? 'bg-amber-500/10 text-amber-600' : 'text-muted-foreground hover:bg-accent/50'"
                            @click="filtroCorte = 'sin_corte'">
                            Sin fecha ({{ totalSinCorte }})
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 space-y-1 overflow-y-auto">
                        <p v-if="!usuariosCorteFiltrados.length"
                            class="px-1 py-4 text-center text-xs text-muted-foreground">
                            Ningún responsable coincide con la búsqueda.
                        </p>

                        <button v-for="usuario in usuariosCorteFiltrados" :key="usuario.id" type="button"
                            class="flex w-full items-center gap-2 rounded-lg border px-2.5 py-2 text-left text-xs transition-colors"
                            :class="seleccionadoCorteId === usuario.id ? 'border-primary bg-primary/5' : 'border-transparent hover:bg-accent'"
                            @click="seleccionarUsuarioCorte(usuario)">
                            <span
                                class="flex size-6 shrink-0 items-center justify-center rounded-full bg-muted text-[10px] font-semibold uppercase">
                                {{ usuario.nombre.slice(0, 2) }}
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-medium">{{ usuario.nombre }}</span>
                                <span class="block truncate text-[10px] text-muted-foreground">
                                    <template v-if="tieneCorte(usuario)">
                                        {{ usuario.totalCompromisos }} compromiso{{ usuario.totalCompromisos === 1 ? ''
                                            : 's' }}
                                        <template v-if="usuario.vencidos > 0"> · {{ usuario.vencidos }} vencido{{
                                            usuario.vencidos === 1 ? '' : 's' }}</template>
                                    </template>
                                    <template v-else>Sin compromisos</template>
                                </span>
                            </span>

                            <Circle v-if="usuario.vencidos > 0" class="size-2 shrink-0 fill-red-500 text-red-500" />
                            <Circle v-else-if="tieneCorte(usuario)"
                                class="size-2 shrink-0 fill-emerald-500 text-emerald-500" />
                            <Circle v-else class="size-2 shrink-0 fill-muted-foreground/40 text-muted-foreground/40" />

                            <ChevronRight class="hidden size-3.5 shrink-0 text-muted-foreground sm:block" />
                        </button>
                    </div>
                </div>

                <!-- Columna 2: lista de compromisos del responsable seleccionado -->
                <div v-if="usuarioCorteSeleccionado && !compromisoEnEdicion" class="flex min-h-0 flex-1 flex-col">
                    <div class="mb-3 flex items-center gap-2">
                        <Button variant="ghost" size="icon" class="size-7 shrink-0 sm:hidden"
                            @click="volverALaListaCorte">
                            <X class="size-4" />
                        </Button>
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-semibold uppercase">
                            {{ usuarioCorteSeleccionado.nombre.slice(0, 2) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold">{{ usuarioCorteSeleccionado.nombre }}</p>
                            <p class="text-[11px] text-muted-foreground">
                                {{ usuarioCorteSeleccionado.totalCompromisos }} compromiso{{
                                    usuarioCorteSeleccionado.totalCompromisos === 1 ? '' : 's' }}
                            </p>
                        </div>
                        <Button size="sm" class="h-7 shrink-0 text-xs" @click="iniciarNuevoCompromiso">
                            <Plus class="mr-1 size-3.5" />
                            Nuevo
                        </Button>
                    </div>

                    <div v-if="cargandoCompromisos"
                        class="flex flex-1 flex-col items-center justify-center gap-2 text-xs text-muted-foreground">
                        <div
                            class="size-5 animate-spin rounded-full border-2 border-muted-foreground/30 border-t-primary" />
                        Cargando compromisos…
                    </div>

                    <div v-else-if="errorCompromisos"
                        class="flex flex-1 flex-col items-center justify-center gap-2 text-center">
                        <AlertCircle class="size-6 text-red-500" />
                        <p class="text-xs text-muted-foreground">{{ errorCompromisos }}</p>
                    </div>

                    <div v-else-if="!compromisosUsuario.length"
                        class="flex flex-1 flex-col items-center justify-center gap-2 px-6 text-center">
                        <CalendarClock class="size-8 text-muted-foreground" />
                        <p class="text-sm font-medium">Sin compromisos configurados</p>
                        <p class="text-xs text-muted-foreground">
                            Puede ser general, ligado a una planta, o a un proyecto puntual.
                        </p>
                    </div>

                    <div v-else class="min-h-0 flex-1 space-y-2 overflow-y-auto pr-1">
                        <div v-for="c in compromisosUsuario" :key="c.id" class="rounded-lg border p-2.5"
                            :class="c.vencido ? 'border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950/20' : 'border-border'">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold">{{ alcanceLabel(c) }}</p>
                                    <p class="text-[11px]"
                                        :class="c.vencido ? 'text-red-600' : 'text-muted-foreground'">
                                        {{ formatCompromisoLargo(c.fecha) }}
                                    </p>
                                    <p v-if="c.nota" class="mt-1 text-[11px] text-muted-foreground">{{ c.nota }}</p>
                                </div>
                                <div class="flex shrink-0 gap-1">
                                    <Button size="icon" variant="ghost" class="size-6" @click="editarCompromiso(c)">
                                        <Pencil class="size-3" />
                                    </Button>
                                    <Button size="icon" variant="ghost" class="size-6 text-red-600 hover:bg-red-100"
                                        @click="pedirConfirmacionEliminar(c.id)">
                                        <Trash2 class="size-3" />
                                    </Button>
                                </div>
                            </div>

                            <div v-if="confirmandoEliminar === c.id"
                                class="mt-2 flex flex-col gap-1.5 rounded-lg border border-red-200 bg-red-50 p-2 text-[11px]">
                                <p class="text-red-700">¿Eliminar este compromiso? Se le notificará del cambio.</p>
                                <div class="flex justify-end gap-1.5">
                                    <Button size="sm" variant="ghost" class="h-6 text-[11px]"
                                        @click="cancelarConfirmacionEliminar">
                                        Cancelar
                                    </Button>
                                    <Button size="sm" variant="destructive" class="h-6 text-[11px]"
                                        :disabled="guardandoCorte" @click="confirmarEliminarCompromiso">
                                        Sí, eliminar
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna 3: formulario (nuevo o edición) -->
                <div v-else-if="compromisoEnEdicion" class="flex min-h-0 flex-1 flex-col">
                    <div class="mb-3 flex items-center gap-2">
                        <Button variant="ghost" size="icon" class="size-7 shrink-0" @click="cancelarEdicionCompromiso">
                            <X class="size-4" />
                        </Button>
                        <p class="text-sm font-semibold">
                            {{ compromisoEnEdicion.id === null ? 'Nuevo compromiso' : 'Editar compromiso' }}
                            <span class="font-normal text-muted-foreground"> · {{ usuarioCorteSeleccionado?.nombre
                            }}</span>
                        </p>
                    </div>

                    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <p class="mb-1 text-xs font-medium text-muted-foreground">Planta (opcional)</p>
                                <Select
                                    :model-value="compromisoEnEdicion.plantaId ? String(compromisoEnEdicion.plantaId) : 'general'"
                                    @update:model-value="(v) => { if (compromisoEnEdicion) compromisoEnEdicion.plantaId = v === 'general' ? null : Number(v); }">
                                    <SelectTrigger class="h-9 text-xs">
                                        <SelectValue placeholder="General" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="general">General (cualquier planta)</SelectItem>
                                        <SelectItem v-for="p in plantaOpciones()" :key="p.id" :value="String(p.id)">
                                            {{ p.nombre }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <p class="mb-1 text-xs font-medium text-muted-foreground">Proyecto (opcional)</p>
                                <Select
                                    :model-value="compromisoEnEdicion.proyectoId ? String(compromisoEnEdicion.proyectoId) : 'toda_planta'"
                                    :disabled="compromisoEnEdicion.plantaId === null"
                                    @update:model-value="(v) => { if (compromisoEnEdicion) compromisoEnEdicion.proyectoId = v === 'toda_planta' ? null : Number(v); }">
                                    <SelectTrigger class="h-9 text-xs">
                                        <SelectValue placeholder="Toda la planta" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="toda_planta">Toda la planta</SelectItem>
                                        <SelectItem v-for="p in proyectoOpcionesPara(compromisoEnEdicion.plantaId)"
                                            :key="p.id" :value="String(p.id)">
                                            {{ p.nombre }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div>
                            <p class="mb-1.5 text-xs font-medium text-muted-foreground">Fecha y hora compromiso</p>

                            <div class="mb-2 rounded-lg border p-2">
                                <div class="mb-1 flex items-center justify-between">
                                    <button type="button" class="rounded-md p-1 hover:bg-accent"
                                        @click="cambiarMesCompromiso(-1)">
                                        <ChevronLeft class="size-3.5" />
                                    </button>
                                    <p class="text-xs font-medium">{{ nombreMesCompromiso }}</p>
                                    <button type="button" class="rounded-md p-1 hover:bg-accent"
                                        @click="cambiarMesCompromiso(1)">
                                        <ChevronRight class="size-3.5" />
                                    </button>
                                </div>

                                <div class="grid grid-cols-7 gap-1 text-center text-[10px] text-muted-foreground">
                                    <span v-for="(d, i) in diasSemana" :key="`compromiso-${d}-${i}`">{{ d }}</span>
                                </div>
                                <div class="mt-1 grid grid-cols-7 gap-1">
                                    <div v-for="(d, idx) in diasCompromisoDisponibles" :key="idx" class="aspect-square"
                                        :class="d.diaMes === null ? 'invisible' : ''">
                                        <button v-if="d.diaMes !== null" type="button" :disabled="d.esPasado"
                                            class="flex size-full flex-col items-center justify-center rounded text-xs transition-colors disabled:cursor-not-allowed disabled:opacity-30"
                                            :class="compromisoEnEdicion.fecha === d.iso
                                                ? 'bg-primary text-primary-foreground'
                                                : 'hover:bg-accent'" @click="d.iso && elegirDiaCompromiso(d.iso)">
                                            {{ d.diaMes }}
                                            <span v-if="d.esHoy" class="mt-0.5 size-1 rounded-full bg-current" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <Input type="time" v-model="compromisoEnEdicion.hora" class="h-9 text-xs" />

                            <p v-if="compromisoEnEdicion.fecha" class="mt-1.5 text-[11px] text-muted-foreground">
                                {{ cuentaRegresiva(`${compromisoEnEdicion.fecha}T${compromisoEnEdicion.hora}`) }}
                            </p>
                        </div>

                        <div>
                            <div class="mb-1.5 flex items-center justify-between">
                                <p class="text-xs font-medium text-muted-foreground">Nota para el responsable</p>
                                <span class="text-[10px] text-muted-foreground">{{ compromisoEnEdicion.nota.length
                                }}/500</span>
                            </div>
                            <Textarea v-model="compromisoEnEdicion.nota"
                                placeholder="Ej. Entrega la planeación de la Planta Norte antes del compromiso, con el cronograma completo de la semana."
                                class="text-xs" rows="3" maxlength="500" />
                            <p class="mt-1 flex items-start gap-1 text-[11px] text-muted-foreground">
                                <Bell class="mt-0.5 size-3 shrink-0" />
                                Esta nota se incluye en la notificación que recibe {{ usuarioCorteSeleccionado?.nombre
                                }}.
                            </p>
                        </div>

                        <div v-if="erroresValidacionCorte.length"
                            class="flex flex-col gap-1 rounded-lg border border-amber-300 bg-amber-50 p-2.5 text-[11px] text-amber-700">
                            <span v-for="(err, i) in erroresValidacionCorte" :key="i" class="flex items-start gap-1.5">
                                <AlertCircle class="mt-0.5 size-3 shrink-0" />
                                {{ err }}
                            </span>
                        </div>

                        <div v-if="errorGuardadoCorte"
                            class="flex items-start gap-1.5 rounded-lg border border-red-300 bg-red-50 p-2.5 text-[11px] text-red-700">
                            <AlertCircle class="mt-0.5 size-3 shrink-0" />
                            {{ errorGuardadoCorte }}
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-end gap-2 border-t pt-3">
                        <Button size="sm" variant="ghost" class="h-8 text-xs" :disabled="guardandoCorte"
                            @click="cancelarEdicionCompromiso">
                            Cancelar
                        </Button>
                        <Button size="sm" class="h-8 text-xs" :disabled="guardandoCorte || !compromisoEsValido"
                            @click="guardarCompromiso">
                            <Save class="mr-1.5 size-3.5" />
                            {{ guardandoCorte ? 'Guardando…' : 'Guardar' }}
                        </Button>
                    </div>
                </div>

                <div v-else class="hidden flex-1 flex-col items-center justify-center gap-2 text-center sm:flex">
                    <UserIcon class="size-8 text-muted-foreground" />
                    <p class="text-sm font-medium">Selecciona un responsable</p>
                    <p class="max-w-[220px] text-xs text-muted-foreground">
                        Elige a alguien de la lista para ver o agregar sus compromisos de entrega.
                    </p>
                </div>
            </div>

            <DialogFooter v-if="!cargandoCorte || cargaCorteCompletada">
                <div class="flex w-full items-center justify-between">
                    <p class="flex items-center gap-1 text-[11px] text-muted-foreground">
                        <Clock class="size-3" />
                        {{ totalConCorte }} de {{ usuariosCorte.length }} responsables con compromiso
                    </p>
                    <Button variant="secondary" size="sm" @click="modalCorteAbierto = false">Cerrar</Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
