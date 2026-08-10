<?php

namespace App\Imports\Ingenierias\Insumos;

use App\Models\Cotizacion;
use App\Support\Ingenierias\Insumos\InsumoParser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;

class InsumosExcelImport
{
    private int $creados = 0;

    /** @var array<int, array<int, string>> */
    private array $errores = [];

    public function __construct(
        private readonly Cotizacion $cotizacion,
        private readonly InsumoParser $parser,
    ) {}

    public function procesar(UploadedFile $archivo): void
    {
        $hojas = $this->leerHojas($archivo);

        $resultado = $this->parser->parsear($hojas);
        $this->errores = $resultado->errores;

        foreach ($resultado->filas as $fila) {
            $this->cotizacion->insumos()->create([
                ...$fila,
                'usuario_carga_id' => auth()->id(),
                'fecha_carga' => now(),
                'estatus' => 'pendiente',
                'activo' => true,
            ]);
            $this->creados++;
        }
    }

    /**
     * Lee TODAS las hojas del libro (no solo la activa). Usamos
     * PhpSpreadsheet directo en vez de Excel::import()/ToCollection
     * porque ese mecanismo, sin WithMultipleSheets, solo entrega la
     * primera hoja al callback — insuficiente para formatos como Walmart
     * que traen una hoja por categoría.
     *
     * @return Collection<string, Collection<int, array<int, mixed>>>
     */
    private function leerHojas(UploadedFile $archivo): Collection
    {
        $spreadsheet = IOFactory::load($archivo->getRealPath());
        $hojas = collect();

        foreach ($spreadsheet->getAllSheets() as $hoja) {
            $filas = collect($hoja->toArray(null, true, true, false));
            $hojas->put($hoja->getTitle(), $filas);
        }

        return $hojas;
    }

    public function creados(): int
    {
        return $this->creados;
    }

    /** @return array<int, array<int, string>> */
    public function errores(): array
    {
        return $this->errores;
    }
}
