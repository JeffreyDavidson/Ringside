<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'email_verified_at',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'role' => Role::class,
    ];

    public function password(): Attribute
    {
        return new Attribute(
            set: fn ($value) => bcrypt($value),
        );
    }

    /**
     * Check to see if the user is an administrator.
     */
    public function isAdministrator(): bool
    {
        return $this->role === Role::ADMINISTRATOR;
    }

    /**
     * Get the user's wrestler.
     */
    public function wrestler(): HasOne
    {
        return $this->hasOne(Wrestler::class);
    }
}
