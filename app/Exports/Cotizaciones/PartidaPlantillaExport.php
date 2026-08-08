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

    private int $filaInicioTabla = 0;

    private int $filaFinTabla = 0;

    /**
     * @param  array<int, array<string, mixed>>  $partidasArbol  resultado de PartidasAction::arbol()
     */
    public function __construct(
        private readonly ?Cotizacion $cotizacion = null,
        private readonly array $partidasArbol = [],
    ) {
        $this->construirFilas();
    }

    private int $filaInicioInstrucciones = 0;

    private int $filaFinInstrucciones = 0;

    private function construirFilas(): void
    {
        $this->filaInicioInstrucciones = count($this->filas) + 1;
        $this->filas[] = ['Cómo llenar esta plantilla:'];
        $this->filas[] = ['- Cada sección (1, 2, 3...) va en su propia fila, solo con No. y Concepto.'];
        $this->filas[] = ['- Cada partida hija va como "No.Hija" (ej. 1.1, 1.2) con Unidad, Cantidad y Precio Unitario.'];
        $this->filas[] = ['- No borres ni muevas las columnas. Borra las filas de ejemplo antes de llenar tus datos.'];
        $this->filaFinInstrucciones = count($this->filas);
        $this->filas[] = [];

        $this->filas[] = ['Cliente:', $this->cotizacion?->cliente ?? ''];
        $this->filas[] = ['Dirección:', $this->cotizacion?->direccion ?? ''];
        $this->filas[] = ['Proveedor:', $this->cotizacion?->proveedor ?? ''];
        $this->filas[] = ['Vendedor:', $this->cotizacion?->vendedor ?? ''];
        $this->filas[] = ['Obra:', $this->cotizacion?->obra ?? ''];
        $this->filas[] = [];
        $this->filasNegritas = [1, 2, 3, 4, 5];

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
            },
        ];
    }
}
