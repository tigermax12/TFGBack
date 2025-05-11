<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clarificacione extends Model
{
    use HasFactory;
    protected $fillable = ['id_orden', 'Operario', 'producto', 'deposito', 'coadyuvantes_extra', 'observaciones'];

    protected $table = 'clarificaciones';
    public function orden()
    {
        return $this->belongsTo(OrdenDeTrabajo::class, 'id_orden');
    }
    public function usuario() {
        return $this->belongsTo(User::class, 'operario');
    }
}
