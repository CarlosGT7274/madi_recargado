<?php

namespace App\Exports\Cotizaciones;

use App\Models\Cotizacion;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PartidaPlantillaExport implements FromArray, WithEvents
{
    /** @var array<int, array<int, mixed>> */
    private array $filas = [];

    /** @var array<int, int> */
    private array $filasNegritas = [];

    /** @var array<int, int> */
    private array $filasEncabezadoDatos = [];

    private int $filaInicioTabla = 0;

    private int $filaFinTabla = 0;

    private int $filaInicioInstrucciones = 0;

    private int $filaFinInstrucciones = 0;

    private int $filaCondicionesHeader = 0;

    private int $filaCondicionesValores = 0;

    private int $filaMoneda = 0;

    private int $filaNota = 0;

    /**
     * @param  array<int, array<string, mixed>>  $partidasArbol  resultado de PartidasAction::arbol()
     */
    public function __construct(
        private readonly ?Cotizacion $cotizacion = null,
        private readonly array $partidasArbol = [],
    ) {
        $this->construirFilas();
    }

    /**
     * Fila "vacía" real: un array con celdas null, NO un array vacío
     * `[]`. Maatwebsite Excel colapsa/ignora las filas totalmente
     * vacías al exportar con FromArray, lo que desalineaba todos los
     * índices de fila calculados con count() — este helper es el fix.
     *
     * @return array<int, null>
     */
    private function filaBlanco(): array
    {
        return [null, null, null, null, null];
    }

    private function construirFilas(): void
    {
        $this->filaInicioInstrucciones = count($this->filas) + 1;
        $this->filas[] = ['Cómo llenar esta plantilla:'];
        $this->filas[] = ['- Cada sección (1, 2, 3...) va en su propia fila, solo con No. y Concepto.'];
        $this->filas[] = ['- Cada partida hija va como "No.Hija" (ej. 1.1, 1.2) con Unidad, Cantidad y Precio Unitario.'];
        $this->filas[] = ['- Al final llena Tiempo de Entrega, Moneda y Notas. El Importe con Letra se calcula solo.'];
        $this->filas[] = ['- No borres ni muevas las columnas. Borra las filas de ejemplo antes de llenar tus datos.'];
        $this->filaFinInstrucciones = count($this->filas);
        $this->filas[] = $this->filaBlanco();

        $this->filasEncabezadoDatos[] = count($this->filas) + 1;
        $this->filas[] = ['Para:', $this->cotizacion?->para ?? ''];
        $this->filasEncabezadoDatos[] = count($this->filas) + 1;
        $this->filas[] = ['Cliente:', $this->cotizacion?->cliente ?? ''];
        $this->filasEncabezadoDatos[] = count($this->filas) + 1;
        $this->filas[] = ['Fecha:', optional($this->cotizacion?->fecha)->format('d/m/Y') ?? ''];
        $this->filasEncabezadoDatos[] = count($this->filas) + 1;
        $this->filas[] = ['Dirección:', $this->cotizacion?->direccion ?? ''];
        $this->filasEncabezadoDatos[] = count($this->filas) + 1;
        $this->filas[] = ['Vendedor:', $this->cotizacion?->vendedor ?? ''];
        $this->filasEncabezadoDatos[] = count($this->filas) + 1;
        $this->filas[] = ['Correo Vendedor:', $this->cotizacion?->correo_vendedor ?? ''];
        $this->filasEncabezadoDatos[] = count($this->filas) + 1;
        $this->filas[] = ['Proveedor:', $this->cotizacion?->proveedor ?? ''];
        $this->filasEncabezadoDatos[] = count($this->filas) + 1;
        $this->filas[] = ['Obra:', $this->cotizacion?->obra ?? ''];
        $this->filas[] = $this->filaBlanco();

        $this->filasNegritas = [...$this->filasEncabezadoDatos];

        $this->filas[] = ['No.', 'Concepto', 'Unidad', 'Cantidad', 'Precio Unitario'];
        $this->filasNegritas[] = count($this->filas);
        $this->filaInicioTabla = count($this->filas);

        // Si ya hay partidas reales (cotización existente), se listan tal cual.
        if (! empty($this->partidasArbol)) {
            foreach ($this->partidasArbol as $padre) {
                $this->filas[] = [$padre['no'], $padre['descripcion'], null, null, null];
                $this->filasNegritas[] = count($this->filas);

                foreach ($padre['hijas'] as $hija) {
                    $this->filas[] = [
                        $hija['no'],
                        $hija['descripcion'],
                        $hija['unidad'],
                        $hija['cantidad'],
                        $hija['precioUnitario'],
                    ];
                }
            }
        } else {
            // Plantilla genérica (sin cotización): se rellena con datos de
            // ejemplo para que quede claro cómo debe llenarse el archivo.
            $this->agregarSeccionEjemplo('1', 'PRELIMINARES', [
                ['1.1', 'Trazo y nivelación', 'm2', 100, 45.50],
                ['1.2', 'Excavación a mano', 'm3', 20, 320.00],
            ]);

            $this->agregarSeccionEjemplo('2', 'CIMENTACIÓN', [
                ['2.1', 'Suministro e instalación de placa', 'pza', 10, 250.50],
            ]);

            $this->agregarSeccionEjemplo('3', 'MULETILLA', [
                ['3.1', 'Traza y contabilidad', 'pza', 50, 25.50],
                ['3.2', 'Mecha de mano', 'pza', 50, 952.00],
            ]);
        }

        $this->filaFinTabla = count($this->filas);
        $this->filas[] = $this->filaBlanco();

        // --- Condiciones comerciales: Tiempo de Entrega | Días de Crédito | Vigencia Cotización ---
        // Encabezados en columnas A, C, E (B y D quedan como separador),
        // así el parser ubica cada etiqueta y lee su valor justo debajo.
        $this->filaCondicionesHeader = count($this->filas) + 1;
        $this->filas[] = ['Tiempo de Entrega', null, 'Días de Crédito', null, 'Vigencia Cotización'];
        $this->filasNegritas[] = $this->filaCondicionesHeader;

        $this->filaCondicionesValores = count($this->filas) + 1;
        $this->filas[] = [
            $this->cotizacion?->tiempo_entrega ?? '07 días después de recibida la Orden de Compra',
            null,
            $this->cotizacion?->dias_credito ?? '30 Días',
            null,
            $this->cotizacion?->vigencia_cotizacion ?? '15 Días',
        ];
        $this->filas[] = $this->filaBlanco();

        // --- Moneda ---
        $this->filaMoneda = count($this->filas) + 1;
        $this->filas[] = ['Moneda:', $this->cotizacion?->moneda ?? 'PESOS MXN'];
        $this->filasNegritas[] = $this->filaMoneda;
        $this->filas[] = $this->filaBlanco();

        // --- Notas: espacio para llenar a mano ---
        $this->filaNota = count($this->filas) + 1;
        $this->filas[] = ['NOTA:', $this->cotizacion?->notas ?? ''];
        $this->filasNegritas[] = $this->filaNota;
    }

    /**
     * @param  array<int, array{0:string,1:string,2:string,3:int|float,4:float}>  $hijas
     */
    private function agregarSeccionEjemplo(string $numero, string $titulo, array $hijas): void
    {
        $this->filas[] = [$numero, $titulo, null, null, null];
        $this->filasNegritas[] = count($this->filas);

        foreach ($hijas as $hija) {
            $this->filas[] = $hija;
        }
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        return $this->filas;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                foreach ($this->filasNegritas as $fila) {
                    $sheet->getStyle("A{$fila}:E{$fila}")->getFont()->setBold(true);
                }

                foreach (range('A', 'E') as $columna) {
                    $sheet->getColumnDimension($columna)->setAutoSize(true);
                }

                // Recuadro de instrucciones: texto en cursiva/gris, sin
                // bordes duros, solo un fondo muy tenue para distinguirlo.
                $rangoInstrucciones = "A{$this->filaInicioInstrucciones}:A{$this->filaFinInstrucciones}";
                $sheet->getStyle($rangoInstrucciones)->getFont()->setItalic(true)->getColor()->setARGB('FF595959');
                $sheet->getStyle("A{$this->filaInicioInstrucciones}")->getFont()->setItalic(false)->setBold(true);
                $sheet->getStyle($rangoInstrucciones)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFF7F7F7');
                $sheet->mergeCellsByColumnAndRow(1, $this->filaInicioInstrucciones, 5, $this->filaInicioInstrucciones);
                for ($f = $this->filaInicioInstrucciones + 1; $f <= $this->filaFinInstrucciones; $f++) {
                    $sheet->mergeCellsByColumnAndRow(1, $f, 5, $f);
                }

                // Datos de encabezado (Para, Cliente, Fecha...): la
                // celda B es donde se llena — resaltada tenue para que
                // se note que es de captura.
                foreach ($this->filasEncabezadoDatos as $fila) {
                    $sheet->getStyle("B{$fila}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFFFF9C4');
                }

                // Encabezado de tabla (No. | Concepto | Unidad | Cantidad | Precio Unitario)
                $filaHeader = $this->filaInicioTabla;
                $sheet->getStyle("A{$filaHeader}:E{$filaHeader}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFEDEDED');

                // Bordes finos y minimalistas: solo líneas delgadas entre
                // celdas, sin marco grueso.
                if ($this->filaFinTabla >= $this->filaInicioTabla) {
                    $rangoTabla = "A{$filaHeader}:E{$this->filaFinTabla}";

                    $sheet->getStyle($rangoTabla)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['argb' => 'FFD0D0D0'],
                            ],
                        ],
                    ]);
                }

                // Alinear cantidad y precio a la derecha dentro de la tabla
                $sheet->getStyle("D{$filaHeader}:E{$this->filaFinTabla}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Condiciones comerciales: encabezado con fondo tenue,
                // fila de valores resaltada como celda de captura.
                $sheet->getStyle("A{$this->filaCondicionesHeader}:E{$this->filaCondicionesHeader}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFEDEDED');
                $sheet->getStyle("A{$this->filaCondicionesHeader}:E{$this->filaCondicionesValores}")
                    ->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['argb' => 'FFD0D0D0'],
                            ],
                        ],
                    ]);
                $sheet->getStyle("A{$this->filaCondicionesValores}:E{$this->filaCondicionesValores}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFF9C4');
                $sheet->getStyle("A{$this->filaCondicionesValores}:E{$this->filaCondicionesValores}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);

                // Moneda: celda de captura resaltada.
                $sheet->getStyle("B{$this->filaMoneda}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFFF9C4');

                // Notas: fila completa combinada B:E, resaltada, con
                // wrap y altura extra para texto largo.
                $sheet->mergeCellsByColumnAndRow(2, $this->filaNota, 5, $this->filaNota);
                $sheet->getStyle("A{$this->filaNota}:E{$this->filaNota}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FFD0D0D0'],
                        ],
                    ],
                ]);
                $sheet->getStyle("B{$this->filaNota}:E{$this->filaNota}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFFF9C4');
                $sheet->getRowDimension($this->filaNota)->setRowHeight(40);
                $sheet->getStyle("B{$this->filaNota}")->getAlignment()
                    ->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            },
        ];
    }
}
