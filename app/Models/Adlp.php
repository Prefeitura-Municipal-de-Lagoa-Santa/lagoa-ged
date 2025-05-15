<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Adlp extends Model
{
    use HasFactory, SoftDeletes; // Use o trait SoftDeletes aqui

    // Se a tabela no DB se chama 'adlp' (singular)
    protected $table = 'adlp';

    // Opcional: definir colunas preenchíveis para mass assignment
    protected $fillable = [
        'numero',
        'ano',
        'tipo',
        'caminho',
    ];
}
   
