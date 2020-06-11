<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable,
        Concerns\Unguarded;


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

    /**
     * Undocumented function
     *
     * @param  string $password
     * @return void
     */
    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = Hash::make($password);
    }
}
