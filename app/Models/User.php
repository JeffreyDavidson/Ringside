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
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $full_name
 * @property string $email
 * @property string|null $email_verified_at
 * @property string $password
 * @property \App\Enums\UserStatus $status
 * @property string|null $avatar_path
 * @property string|null $phone_number
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Enums\Role $role
 * @property-read \App\Models\TFactory|null $use_factory
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read \App\Models\Wrestler|null $wrestler
 *
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \App\Builders\UserBuilder newModelQuery()
 * @method static \App\Builders\UserBuilder newQuery()
 * @method static \App\Builders\UserBuilder query()
 *
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    /** @use HasBuilder<UserBuilder> */
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
        'avavar_path',
        'phone_number',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => UserStatus::Unverified->value,
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

    public function getAvatar(): string
    {
        return $this->avatar_path ?? 'blank.png';
    }

    /**
     * Retrieve the readable phone number of the model.
     */
    public function getFormattedPhoneNumber(): string
    {
        return $this->phone_number ? sprintf('(%s) %s-%s', substr($this->phone_number, 0, 3), substr($this->phone_number, 3, 3), substr($this->phone_number, 6, 4)) : '';
    }
}
