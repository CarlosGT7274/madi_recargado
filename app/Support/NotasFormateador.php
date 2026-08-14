<?php

namespace App\Support;

final class NotasFormateador
{
    /**
     * Convierte texto libre (capturado en Excel con Alt+Enter, o en el
     * textarea de Notas) a bloques estructurados, SIN generar HTML.
     * Blade/Vue renderizan estos bloques con interpolación normal
     * ({{ }}), así que el contenido nunca se interpreta como markup —
     * cero riesgo de XSS sin importar lo que traiga el Excel.
     *
     * Convención (la misma que la gente ya usa a mano en Excel):
     *   - punto     -> lista con viñetas
     *   * punto     -> lista con viñetas
     *   • punto     -> lista con viñetas
     *   1. paso     -> lista numerada
     *   1) paso     -> lista numerada
     *   cualquier otra línea -> párrafo normal
     *
     * @return array<int, array{tipo: 'parrafo'|'lista'|'lista_numerada', texto: ?string, items: ?array<int, string>}>
     */
    public static function bloques(?string $notas): array
    {
        if ($notas === null || trim($notas) === '') {
            return [];
        }

        $lineas = preg_split('/\r\n|\r|\n/', trim($notas)) ?: [];
        $bloques = [];
        $itemsActuales = [];
        $tipoActual = null;

        $cerrarLista = function () use (&$bloques, &$itemsActuales, &$tipoActual) {
            if ($itemsActuales !== []) {
                $bloques[] = ['tipo' => $tipoActual, 'texto' => null, 'items' => $itemsActuales];
            }
            $itemsActuales = [];
            $tipoActual = null;
        };

        foreach ($lineas as $linea) {
            $linea = trim($linea);

            if ($linea === '') {
                $cerrarLista();

                continue;
            }

            if (preg_match('/^[-*•]\s+(.+)$/u', $linea, $m)) {
                if ($tipoActual !== 'lista') {
                    $cerrarLista();
                    $tipoActual = 'lista';
                }
                $itemsActuales[] = $m[1];

                continue;
            }

            if (preg_match('/^\d+[.)]\s+(.+)$/u', $linea, $m)) {
                if ($tipoActual !== 'lista_numerada') {
                    $cerrarLista();
                    $tipoActual = 'lista_numerada';
                }
                $itemsActuales[] = $m[1];

                continue;
            }

            $cerrarLista();
            $bloques[] = ['tipo' => 'parrafo', 'texto' => $linea, 'items' => null];
        }

        $cerrarLista();

        return $bloques;
    }
}
