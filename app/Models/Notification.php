<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use MongoDB\Laravel\Eloquent\Model as Eloquent;
use Carbon\Carbon;

class Notification extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'notifications';

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type', // success, error, warning, info, job
        'category', // job, system, user_action, import, permission
        'data', // dados extras (job_id, document_count, etc)
        'read_at',
        'priority', // low, normal, high, urgent
        'expires_at',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Scopes para facilitar consultas
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', (string) $userId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', Carbon::now());
        });
    }

    // Métodos auxiliares
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    public function markAsRead(): bool
    {
        $this->read_at = Carbon::now();
        return $this->save();
    }

    public function markAsUnread(): bool
    {
        $this->read_at = null;
        $this->timestamps = false; // Evitar atualizar updated_at
        $result = $this->save();
        $this->timestamps = true;
        return $result;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    // Formatação para frontend
    public function toArray()
    {
        $array = parent::toArray();
        $array['is_read'] = $this->isRead();
        $array['is_expired'] = $this->isExpired();
        $array['time_ago'] = $this->created_at ? $this->created_at->diffForHumans() : null;
        return $array;
    }

    // Método estático para limpeza automática
    public static function cleanupExpired()
    {
        return static::where('expires_at', '<', Carbon::now())->delete();
    }

    // Método estático para limpeza por idade
    public static function cleanupOld($days = 7)
    {
        return static::where('created_at', '<', Carbon::now()->subDays($days))->delete();
    }
}
