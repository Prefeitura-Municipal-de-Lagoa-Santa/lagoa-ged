<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use MongoDB\Laravel\Eloquent\Model as Eloquent;
use MongoDB\Laravel\Eloquent\HybridRelations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use MongoDB\Laravel\Eloquent\SoftDeletes;

use LdapRecord\Laravel\Auth\AuthenticatesWithLdap;
use LdapRecord\Laravel\Auth\LdapAuthenticatable;
use LdapRecord\Laravel\Auth\HasLdapUser;
use MongoDB\BSON\ObjectId;



class User extends Authenticatable implements LdapAuthenticatable
{
    use HasFactory, Notifiable, SoftDeletes;
    use HybridRelations;
    use AuthenticatesWithLdap;

    //protected $primaryKey = 'id';
    //public $incrementing = false;
    //protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'full_name',
        'email',
        'password',
        'guid',
        'domain',
    ];

    protected $appends = ['is_protected','is_ldap','group_ids'];

    protected function getIsProtectedAttribute(): bool
    {
        // Defina aqui sua lógica. Pode checar por nome, slug, ou ID.
        // Esta abordagem permite adicionar mais nomes facilmente no futuro.
        $protectedUsernames = config('permissions.protected_usernames', []);

        $protectedUsernamesUpper = array_map('strtoupper', $protectedUsernames);
        
        return in_array(strtoupper($this->username), $protectedUsernamesUpper);
    }

    protected function getIsLdapAttribute(): bool
    {
        // Defina aqui sua lógica. Pode checar por nome, slug, ou ID.
        // Esta abordagem permite adicionar mais nomes facilmente no futuro.
        return !empty($this->domain);
    }

   public function getGroupIdsAttribute(): array
    {
        // Cache simples para evitar múltiplas consultas na mesma requisição
        if (array_key_exists('group_ids', $this->attributes)) {
            return $this->attributes['group_ids'];
        }

        // Garante que o ID do usuário (this->_id) é um ObjectId para a consulta
        $userIdAsObjectId = ($this->_id instanceof ObjectId) ? $this->_id : new ObjectId($this->_id);

        // Busca os _id (ObjectIds) dos grupos onde o ObjectId do usuário
        // está presente no array 'user_ids' da coleção Group.
        // O pluck('_id')->toArray() pode retornar strings aqui dependendo da versão/config.
        $rawGroupIdsFromDb = Group::where('user_ids', $userIdAsObjectId)->pluck('id')->toArray();
        //dd($rawGroupIdsFromDb);
        // ** AQUI ESTÁ A MUDANÇA CRUCIAL: Mapeia e converte cada ID para uma instância de ObjectId **
        $groupIds = array_map(function($id) {
            // Verifica se já é um ObjectId (para segurança, embora não seja o caso relatado)
            // ou se é uma string válida para conversão.
            if ($id instanceof ObjectId) {
                return $id;
            }
            // Se for string, tenta converter.
            // Poderíamos adicionar mais validação aqui se o $id pudesse ser nulo ou malformado.
            return new ObjectId($id);
        }, $rawGroupIdsFromDb);


        // Armazena o resultado no atributo 'attributes' para caching básico no modelo.
        $this->attributes['group_ids'] = $groupIds;

        return $groupIds;
    }

    public function isAdmin(): bool
    {
        // Garante que o usuário tem um grupo antes de tentar acessar o nome

        $adminGroup = Group::where('name', 'ADMINISTRADORES')->first();

        if (!$adminGroup) {
            return false;
        }

        if (empty($adminGroup->user_ids) || !is_array($adminGroup->user_ids)) {
            return false;
        }

        return in_array($this->_id, $adminGroup->user_ids);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
