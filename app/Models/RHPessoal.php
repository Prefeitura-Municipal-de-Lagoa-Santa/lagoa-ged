<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RHPessoal extends Model
{
    use HasFactory, SoftDeletes; // Use o trait SoftDeletes aqui
    protected $talbe = 'rhpessoal';
    protected $fillable = [
        'matricula',
        'nome',
        'situacao',
        'caminho',
    ];
}
