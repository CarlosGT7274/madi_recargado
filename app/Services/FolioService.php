<?php

namespace App\Services;

use App\Models\Folio;
use Illuminate\Support\Facades\DB;

class FolioService
{
    public function siguiente(string $modulo, string $tipo, string $prefijo): string
    {
        return DB::transaction(function () use ($modulo, $tipo, $prefijo) {
            $folio = Folio::where('modulo', $modulo)
                ->where('tipo', $tipo)
                ->lockForUpdate()
                ->first();

            if (! $folio) {
                $folio = Folio::create([
                    'modulo' => $modulo,
                    'tipo' => $tipo,
                    'prefijo' => $prefijo,
                    'ultimo_numero' => 0,
                ]);
            }

            $folio->increment('ultimo_numero');

            return $folio->prefijo.'-'.str_pad((string) $folio->ultimo_numero, 4, '0', STR_PAD_LEFT);
        });
    }
}
