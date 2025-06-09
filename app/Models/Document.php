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
        'file_size',
        'upload_date',
        'uploaded_by',
        'metadata',
        'tags',
        'status',
        'permissions',
        'file_location',
    ];

    protected $casts = [
        'upload_date' => 'datetime',
        'file_size' => 'integer',
        'metadata' => 'object', // Ou 'object' dependendo de como você quer acessá-lo
        'metadata.document_year' => 'integer', // Exemplo de cast para campo aninhado
        'tags' => 'array',
        'permissions' => 'object', // Ou 'object'
        'permissions.read_group_ids' => 'array',
        'permissions.write_group_ids' => 'array',
        'permissions.deny_group_ids' => 'array',
        'file_location' => 'object', // Ou 'object'
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by', '_id');
    }

    public function readableByGroups()
    {
        return $this->belongsToMany(Group::class, null, 'permissions.read_group_ids', '_id');
    }
}
