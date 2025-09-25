<?php

namespace App\Models;

//use Illuminate\Database\Eloquent\Model;
use MongoDB\Laravel\Eloquent\Model; // Importe o Model do MongoDB
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\SoftDeletes;
use MongoDB\BSON\ObjectId;
class Document extends Model
{
    use HasFactory, SoftDeletes; // Use SoftDeletes se necessário

    protected $fillable = [
        'title',
        'filename',
        'file_extension',
        'mime_type',
        //'file_size',
        'upload_date',
        'uploaded_by',
        'metadata',
        'tags',
        'status',
        'permissions',
        'file_location',
    ];

    //protected $casts = [
    //'upload_date' => 'datetime',
    //    'file_size' => 'integer',
    //    'metadata' => 'array', // Ou 'object' dependendo de como você quer acessá-lo
    //    'tags' => 'array',
    //    'permissions' => 'array', // Ou 'object'
    //    'file_location' => 'array', // Ou 'object'
    //];

    

}
