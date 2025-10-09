<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = ['name', 'email', 'password'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relationships
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id');
    }

    // Helper fallback (optional)
    public function roleNames(): array
    {
        try {
            return $this->roles()->pluck('name')->toArray();
        } catch (\Throwable $e) {
            return $this->role ? [$this->role] : [];
        }
    }

    public function hasRoleName(string $name): bool
    {
        return in_array($name, $this->roleNames(), true);
    }

    public function hasAnyRole(array $names): bool
    {
        return count(array_intersect($this->roleNames(), $names)) > 0;
    }
}
