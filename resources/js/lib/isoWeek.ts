/**
 * Cálculo de semana ISO-8601 (lunes-domingo, semana 1 = la que contiene el
 * primer jueves del año). Antes vivía duplicado en Create.vue; ahora lo usan
 * también Planificador.vue (preselección de semana al crear desde un día).
 */

export function toIso(fecha: Date): string {
    return `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}-${String(fecha.getDate()).padStart(2, '0')}`;
}

export function isoWeekInfo(fecha: Date): { semana: number; anio: number } {
    const copia = new Date(Date.UTC(fecha.getFullYear(), fecha.getMonth(), fecha.getDate()));
    const diaSemana = (copia.getUTCDay() + 6) % 7;
    copia.setUTCDate(copia.getUTCDate() - diaSemana + 3);
    const primerJueves = new Date(Date.UTC(copia.getUTCFullYear(), 0, 4));
    const semana = 1 + Math.round(((copia.getTime() - primerJueves.getTime()) / 86400000 - 3 + ((primerJueves.getUTCDay() + 6) % 7)) / 7);
    return { semana, anio: copia.getUTCFullYear() };
}

export function lunesDeSemanaIso(anio: number, semana: number): Date {
    const cuatroEnero = new Date(anio, 0, 4);
    const diaSemanaCuatroEnero = (cuatroEnero.getDay() + 6) % 7;
    const lunesSemanaUno = new Date(cuatroEnero);
    lunesSemanaUno.setDate(cuatroEnero.getDate() - diaSemanaCuatroEnero);

    const resultado = new Date(lunesSemanaUno);
    resultado.setDate(lunesSemanaUno.getDate() + (semana - 1) * 7);
    return resultado;
}

/** Los 7 días (lunes→domingo) que componen una semana ISO. */
export function diasDeSemana(anio: number, semana: number): Date[] {
    const lunes = lunesDeSemanaIso(anio, semana);
    return Array.from({ length: 7 }, (_, i) => {
        const dia = new Date(lunes);
        dia.setDate(lunes.getDate() + i);
        return dia;
    });
}

export function mismaSemanaIso(a: Date, b: Date): boolean {
    const infoA = isoWeekInfo(a);
    const infoB = isoWeekInfo(b);
    return infoA.anio === infoB.anio && infoA.semana === infoB.semana;
}
