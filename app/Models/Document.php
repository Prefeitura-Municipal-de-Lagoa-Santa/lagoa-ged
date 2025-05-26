<?php

namespace App\Models;

//use Illuminate\Database\Eloquent\Model;
use MongoDB\Laravel\Eloquent\Model; // Importe o Model do MongoDB
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
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
        'upload_date' => 'date',
        'uploaded_by' => 'objectid',
        'metadata' => 'object',
        'tags' => 'array',
        'permissions' => 'object',
        'file_location' => 'object',
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
