<?php

namespace App\Models\Concerns;

use App\Models\Archivo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasArchivos
{
    public function archivos(): MorphMany
    {
        return $this->morphMany(Archivo::class, 'archivable');
    }

    public function imagenes(): MorphMany
    {
        return $this->archivos()->where('tipo_archivo', 'imagen')->orderBy('orden');
    }
}
