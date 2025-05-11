<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trasiego extends Model
{
    use HasFactory;
    protected $fillable = ['id_orden', 'Operario', 'producto', 'deposito_origen', 'deposito_destino', 'cantidad_a_trasegar', 'tipo_de_limpieza', 'observaciones'];

    protected $table = 'trasiegos';
    public function orden()
    {
        return $this->belongsTo(OrdenDeTrabajo::class, 'id_orden');
    }
    public function usuario() {
        return $this->belongsTo(User::class, 'operario');
    }
}
