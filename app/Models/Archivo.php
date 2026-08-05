<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Archivo extends Model
{
    protected $table = 'archivos';

    const CREATED_AT = 'fecha_creacion';

    const UPDATED_AT = null;

    protected $fillable = [
        'archivable_type',
        'archivable_id',
        'almacenamiento',
        'nombre_archivo',
        'tipo_archivo',
        'tipo_mime',
        'tamano_bytes',
        'contenido_base64',
        'url',
        'storage_driver',
        'descripcion',
        'orden',
        'usuario_id',
    ];

    protected $casts = [

    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function archivable()
    {
        return $this->morphTo();
    }

    public function urlPublica(): ?string
    {
        if ($this->almacenamiento !== 'url' || ! $this->url) {
            return null;
        }

        return Storage::disk($this->storage_driver ?? 'public')->url($this->url);
    }
}
