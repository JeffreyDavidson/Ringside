<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * Check to see if the user is a super administrator.
     *
     * @return bool
     */
    public function isSuperAdministrator()
    {
        return $this->role === Role::SUPER_ADMINISTRATOR()->label;
    }

    /**
     * Check to see if the user is an administrator.
     *
     * @return bool
     */
    public function isAdministrator()
    {
        return $this->role === Role::ADMINISTRATOR()->label;
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
