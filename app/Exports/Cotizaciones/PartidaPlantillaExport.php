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
    private const COLOR_TITULO_FONDO = 'FF1F2937';

    private const COLOR_TITULO_TEXTO = 'FFFFFFFF';

    private const COLOR_SECCION = 'FFEDEDED';

    private const COLOR_CAPTURA = 'FFFFF9C4';

    private const COLOR_INSTRUCCIONES = 'FFF7F7F7';

    private const COLOR_BORDE = 'FFD0D0D0';

    /** @var array<int, array<int, mixed>> */
    private array $filas = [];

    /** Filas con texto en negrita (títulos de sección). */
    private array $filasNegritas = [];

    /** Filas cuya celda B es de captura manual — se resaltan en amarillo. */
    private array $filasCaptura = [];

    private int $filaTitulo = 0;

    private int $filaInicioInstrucciones = 0;

    private int $filaFinInstrucciones = 0;

    private int $filaSeccionDatos = 0;

    private int $filaInicioTabla = 0;

    private int $filaFinTabla = 0;

    private int $filaCondicionesHeader = 0;

    private int $filaCondicionesValores = 0;

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
     * Fila "vacía" real: array con celdas null, NO un array vacío `[]`.
     * Maatwebsite Excel colapsa las filas totalmente vacías con
     * FromArray, lo que desalinea los índices calculados con count().
     *
     * @return array<int, null>
     */
    private function filaBlanco(): array
    {
        return [null, null, null, null, null];
    }

    private function construirFilas(): void
    {
        // --- Título ---
        $this->filaTitulo = count($this->filas) + 1;
        $this->filas[] = ['PLANTILLA DE COTIZACIÓN', null, null, null, null];
        $this->filas[] = $this->filaBlanco();

        // --- Instrucciones ---
        $this->filaInicioInstrucciones = count($this->filas) + 1;
        $this->filas[] = ['Instrucciones'];
        $this->filas[] = ['1. Llena los datos amarillos de "Datos del Cliente y del Proyecto".'];
        $this->filas[] = ['2. En la tabla: cada sección va con un número solo (1, 2, 3...). Cada partida va como "No.Hija" (1.1, 1.2...) con Unidad, Cantidad y Precio Unitario.'];
        $this->filas[] = ['3. Al final llena Tiempo de Entrega, Días de Crédito, Vigencia y Notas.'];
        $this->filas[] = ['4. Borra las filas de ejemplo antes de llenar tus datos. No borres ni muevas columnas.'];
        $this->filaFinInstrucciones = count($this->filas);
        $this->filas[] = $this->filaBlanco();

        // --- Datos del Cliente y del Proyecto ---
        $this->filaSeccionDatos = count($this->filas) + 1;
        $this->filas[] = ['Datos del Cliente y del Proyecto', null, null, null, null];
        $this->filasNegritas[] = $this->filaSeccionDatos;

        $this->agregarCampoCaptura('Fecha:', optional($this->cotizacion?->fecha)->format('d/m/Y') ?? '');
        $this->agregarCampoCaptura('Cliente:', $this->cotizacion?->cliente ?? '');
        $this->agregarCampoCaptura('Dirección:', $this->cotizacion?->direccion ?? '');
        $this->agregarCampoCaptura('Obra:', $this->cotizacion?->obra ?? '');
        $this->agregarCampoCaptura('Vendedor:', $this->cotizacion?->vendedor ?? '');
        $this->agregarCampoCaptura('Proveedor:', $this->cotizacion?->proveedor ?? '');
        $this->filas[] = $this->filaBlanco();

        // --- Tabla de partidas: un solo encabezado, categorías y subcategorías ---
        $this->filas[] = ['No.', 'Concepto', 'Unidad', 'Cantidad', 'Precio Unitario'];
        $this->filasNegritas[] = count($this->filas);
        $this->filaInicioTabla = count($this->filas);

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

        // --- Condiciones comerciales ---
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

        // --- Notas ---
        $this->filaNota = count($this->filas) + 1;
        $this->filas[] = ['NOTA:', $this->cotizacion?->notas ?? ''];
        $this->filasNegritas[] = $this->filaNota;
    }

    private function agregarCampoCaptura(string $etiqueta, string $valor): void
    {
        $this->filasCaptura[] = count($this->filas) + 1;
        $this->filas[] = [$etiqueta, $valor];
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

                // Título
                $sheet->mergeCellsByColumnAndRow(1, $this->filaTitulo, 5, $this->filaTitulo);
                $sheet->getStyle("A{$this->filaTitulo}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => self::COLOR_TITULO_TEXTO]],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::COLOR_TITULO_FONDO]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension($this->filaTitulo)->setRowHeight(28);

                foreach ($this->filasNegritas as $fila) {
                    $sheet->getStyle("A{$fila}:E{$fila}")->getFont()->setBold(true);
                }

                foreach (range('A', 'E') as $columna) {
                    $sheet->getColumnDimension($columna)->setAutoSize(true);
                }

                // Instrucciones: cursiva/gris, fondo tenue, sin bordes duros
                $rangoInstrucciones = "A{$this->filaInicioInstrucciones}:A{$this->filaFinInstrucciones}";
                $sheet->getStyle($rangoInstrucciones)->getFont()->setItalic(true)->getColor()->setARGB('FF595959');
                $sheet->getStyle("A{$this->filaInicioInstrucciones}")->getFont()->setItalic(false)->setBold(true);
                $sheet->getStyle($rangoInstrucciones)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_INSTRUCCIONES);
                for ($f = $this->filaInicioInstrucciones; $f <= $this->filaFinInstrucciones; $f++) {
                    $sheet->mergeCellsByColumnAndRow(1, $f, 5, $f);
                }

                // Sección "Datos del Cliente y del Proyecto"
                $sheet->mergeCellsByColumnAndRow(1, $this->filaSeccionDatos, 5, $this->filaSeccionDatos);
                $sheet->getStyle("A{$this->filaSeccionDatos}:E{$this->filaSeccionDatos}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_SECCION);

                foreach ($this->filasCaptura as $fila) {
                    $sheet->getStyle("B{$fila}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_CAPTURA);
                }

                // Encabezado de tabla de partidas
                $filaHeader = $this->filaInicioTabla;
                $sheet->getStyle("A{$filaHeader}:E{$filaHeader}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_SECCION);

                if ($this->filaFinTabla >= $filaHeader) {
                    $sheet->getStyle("A{$filaHeader}:E{$this->filaFinTabla}")->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => self::COLOR_BORDE]],
                        ],
                    ]);
                }

                $sheet->getStyle("D{$filaHeader}:E{$this->filaFinTabla}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Condiciones comerciales
                $sheet->getStyle("A{$this->filaCondicionesHeader}:E{$this->filaCondicionesHeader}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_SECCION);
                $sheet->getStyle("A{$this->filaCondicionesHeader}:E{$this->filaCondicionesValores}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => self::COLOR_BORDE]],
                    ],
                ]);
                $sheet->getStyle("A{$this->filaCondicionesValores}:E{$this->filaCondicionesValores}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_CAPTURA);
                $sheet->getStyle("A{$this->filaCondicionesValores}:E{$this->filaCondicionesValores}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);

                // Notas: fila combinada B:E, resaltada, con wrap
                $sheet->mergeCellsByColumnAndRow(2, $this->filaNota, 5, $this->filaNota);
                $sheet->getStyle("A{$this->filaNota}:E{$this->filaNota}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => self::COLOR_BORDE]],
                    ],
                ]);
                $sheet->getStyle("B{$this->filaNota}:E{$this->filaNota}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_CAPTURA);
                $sheet->getRowDimension($this->filaNota)->setRowHeight(40);
                $sheet->getStyle("B{$this->filaNota}")->getAlignment()
                    ->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            },
        ];
    }
}
