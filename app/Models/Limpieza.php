<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Limpieza extends Model
{
    use HasFactory;
    protected $fillable = ['id_orden', 'Operario', 'deposito', 'tipo_de_limpieza', 'observaciones'];

    protected $table = 'limpiezas';
    public function orden()
    {
        return $this->belongsTo(OrdenDeTrabajo::class, 'id_orden');
    }
    public function usuario() {
        return $this->belongsTo(User::class, 'operario');
    }

}
