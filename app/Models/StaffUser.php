<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class StaffUser extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable;

    protected $table = 'staff_users';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['email', 'password', 'display_name', 'role', 'active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'active' => 'boolean', 'last_login_at' => 'datetime'];
    }
}
