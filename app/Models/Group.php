<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
//use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\SoftDeletes;

class Group extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'user_ids', // Array de IDs de usuários
    ];

    /**
     * Adiciona o nosso acessor 'members' para que ele seja incluído
     * sempre que o model for convertido para array ou JSON.
     */
    protected $appends = ['members', 'is_protected'];

    protected function getIsProtectedAttribute(): bool
    {
        $protectedNames = config('permissions.protected_groups', []);

        $protectedNamesUpper = array_map('strtoupper', $protectedNames);

        return in_array(needle: strtoupper($this->name), haystack: $protectedNamesUpper);
    }

    /**
     * Este é o nosso acessor customizado.
     * O Laravel executa esta função automaticamente quando tentamos acessar a propriedade "members".
     * Ex: $group->members
     */
    public function getMembersAttribute()
    {
        // Se a relação já foi carregada manualmente, apenas a retorna.
        if (array_key_exists('members', $this->relations)) {
            return $this->relations['members'];
        }

        // Pega os IDs do array 'user_ids' deste grupo.
        $memberIds = $this->user_ids ?? [];

        // Se não houver IDs, retorna uma coleção vazia para evitar erros.
        if (empty($memberIds)) {
            return collect();
        }

        // Executa a busca manual que NÓS SABEMOS QUE FUNCIONA.
        $members = User::whereIn('_id', $memberIds)->get();

        // Armazena o resultado na propriedade 'relations' para que não precise ser buscado novamente.
        return $this->setRelation('members', $members);
    }



    public function readableDocuments()
    {
        return $this->belongsToMany(Document::class, null, 'permissions.read_group_ids', '_id');
    }
}
