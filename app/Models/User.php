<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\UserBuilder;
use App\Enums\Role;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\HasBuilder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property Role $role
 */
class User extends Authenticatable
{
    /** @use HasBuilder<UserBuilder<static>> */
    use HasBuilder;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'email_verified_at',
        'password',
        'role',
        'status',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => UserStatus::Unverified,
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected static string $builder = UserBuilder::class;

    /**
     * Get the user's password.
     *
     * @return Attribute<mixed, mixed>
     */
    public function password(): Attribute
    {
        return new Attribute(
            set: fn (string $value) => bcrypt($value),
        );
    }

    /**
     * Check to see if the user is an administrator.
     */
    public function isAdministrator(): bool
    {
        return $this->role === Role::Administrator;
    }

    /**
     * Get the user's wrestler.
     *
     * @return HasOne<Wrestler, $this>
     */
    public function wrestler(): HasOne
    {
        return $this->hasOne(Wrestler::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'status' => UserStatus::class,
        ];
    }
}
