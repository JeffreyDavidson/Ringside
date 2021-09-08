<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use Notifiable,
        HasFactory,
        Concerns\Unguarded;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'role' => Role::class,
    ];

    /**
     * Check to see if the user is a super administrator.
     *
     * @return bool
     */
    public function isSuperAdministrator()
    {
        return $this->role->is(Role::SUPER_ADMINISTRATOR);
    }

    /**
     * Check to see if the user is an administrator.
     *
     * @return bool
     */
    public function isAdministrator()
    {
        return $this->role->is(Role::ADMINISTRATOR);
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
