<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Saude extends Model
{
    use HasFactory, SoftDeletes; // Use o trait SoftDeletes aqui
    protected $table = 'saude';
    protected $fillable = [
        'atendimento',
        'nome',
        'secretaria',
        'tipo',
        'data',
        'caminho',
    ];
}
