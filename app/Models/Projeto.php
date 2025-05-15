<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Projeto extends Model
{
    use HasFactory, SoftDeletes; // Use o trait SoftDeletes aqui

    // Opcional: definir colunas preenchíveis para mass assignment
    protected $fillable = [
        'ano',
        'numero',
        'caminho',
    ];
}
