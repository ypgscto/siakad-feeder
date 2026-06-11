<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'siakad_user_id',
        'siakad_login',
        'jenis_user',
        'role',
        'prodi_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function canLoginToFeeder(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->isProdi()) {
            return trim((string) $this->prodi_id) !== '';
        }

        return in_array($this->role, ['superadmin', 'admin'], true);
    }

    public function isSuperadmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isProdi(): bool
    {
        return $this->role === 'prodi';
    }

    public function isSiakadSourced(): bool
    {
        return trim((string) $this->siakad_user_id) !== '';
    }
}
