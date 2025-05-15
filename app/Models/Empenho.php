<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Empenho extends Model
{
    use HasFactory, SoftDeletes; // Use o trait SoftDeletes aqui

    // Opcional: definir colunas preenchíveis para mass assignment
    protected $fillable = [
            'empenho',                   
            'liquidacao',                  
            'ano',                   
            'tipo',                
            'ficha',               
            'numero_unidade',                  
            'unidade',              
            'numero_subunidade',               
            'subunidade',               
            'numero_fonte_recurso',                
            'fonte_recurso',            
            'numero_fornecedor',               
            'fornecedor',               
            'numero_modalidade',               
            'modalidade',               
            'licitacao',            
            'ano_licitacao',               
            'processo_compra',                   
            'banco',               
            'conta',                 
            'modelo',                  
            'caminho',                  
    ];
}
