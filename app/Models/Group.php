<?php

namespace App\Models;

//use MongoDB\Laravel\Eloquent\Model; 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'members', // Array de IDs de usuários
    ];

    protected $casts = [
        'members' => 'array', // Campo 'members' é um array
    ];

    public function members()
    {
        return $this->belongsToMany(User::class, null, 'members', '_id');
    }

    public function readableDocuments()
    {
        return $this->belongsToMany(Document::class, null, 'permissions.read_group_ids', '_id');
    }
}
