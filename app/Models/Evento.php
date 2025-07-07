<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evento extends Model
{
    use HasFactory;
    protected $fillable = [
        'fecha',
        'paquete_id',
        'numero_personas',
        'nombre_cliente',
        'email_cliente',
        'telefono_cliente',
        'estatus',
    ];

    public function paquete()
    {
        return $this->belongsTo(Paquete::class);
    }
}
