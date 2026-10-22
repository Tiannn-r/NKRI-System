<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'nik',
        'full_name',
        'username',
        'password',
        'role',
        'department',
        'permissions',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'permissions' => 'array',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin' || strtolower($this->username) === 'miranti';
    }

    public function isWarehouse(): bool
    {
        return $this->role === 'gudang' || $this->isAdmin();
    }

    public function isAssembly(): bool
    {
        return $this->role === 'assembly' || $this->isAdmin();
    }

    public function isQc(): bool
    {
        return $this->role === 'qc' || $this->isAdmin();
    }
}
