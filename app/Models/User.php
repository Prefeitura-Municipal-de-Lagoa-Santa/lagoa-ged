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

    protected $appends = ['is_protected','is_ldap'];

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

    public function group()
    {
        return $this->belongsTo(Group::class);
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
