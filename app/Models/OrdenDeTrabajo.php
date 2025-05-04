<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenDeTrabajo extends Model
{
    use HasFactory;
    protected $fillable = [
        'Tipo_de_Orden',
        'Prioridad',
        'Estado',
        'Id_user_creador',
        'Fecha_de_modificacion',
        'Fecha_de_realizacion',
        'Fecha_de_creacion'
    ];
    protected $table = 'orden_de_trabajo'; // 👈 nombre real de la tabla
    protected $primaryKey = 'id_orden';
    public function user()
    {
        return $this->belongsTo(User::class, 'Id_user_creador');
    }

    public function limpieza() {
        return $this->hasOne(Limpieza::class, 'id_orden');
    }

    public function trasiego() {
        return $this->hasOne(Trasiego::class, 'id_orden');
    }

    public function clarificacion() {
        return $this->hasOne(Clarificacione::class, 'id_orden');
    }
}
