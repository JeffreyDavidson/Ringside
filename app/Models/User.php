<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory,
        Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['first_name', 'last_name', 'email', 'email_verified_at', 'password', 'role'];

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
     *
     * @return bool
     */
    public function isAdministrator()
    {
        return $this->role->equals(Role::administrator());
    }

    /**
     * Get the user's wrestler.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function wrestler()
    {
        return $this->hasOne(Wrestler::class);
    }
}
