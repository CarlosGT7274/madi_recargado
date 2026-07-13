<?php

namespace App\Models;

use App\Enums\CategoriaInventario;
use App\Enums\EstadoInventarioItem;
use Illuminate\Database\Eloquent\Model;

class InventarioItem extends Model
{
    protected $table = 'inventario_items';

    protected $fillable = [
        'categoria',
        'codigo',
        'nombre',
        'descripcion',
        'cantidad',
        'unidad',
        'ubicacion',
        'estado',
        'atributos_extra',
        'usuario_registro_id',
    ];

    protected $casts = [
        'categoria' => CategoriaInventario::class,
        'estado' => EstadoInventarioItem::class,
        'atributos_extra' => 'array',
    ];

    public function usuarioRegistro()
    {
        return $this->belongsTo(User::class, 'usuario_registro_id');
    }

    public function prestamos()
    {
        return $this->hasMany(HerramientaPrestamo::class, 'inventario_item_id');
    }
}
