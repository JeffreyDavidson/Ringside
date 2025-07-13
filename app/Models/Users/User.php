<?php

declare(strict_types=1);

namespace App\Models\Users;

use App\Builders\Users\UserBuilder;
use App\Enums\Users\Role;
use App\Enums\Users\UserStatus;
use App\Models\Wrestlers\Wrestler;
use Database\Factories\Users\UserFactory;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $full_name
 * @property string $email
 * @property string|null $email_verified_at
 * @property string $password
 * @property UserStatus $status
 * @property string|null $avatar_path
 * @property string|null $phone_number
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Role $role
 * @property-read string $formatted_phone_number
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read Wrestler|null $wrestler
 * @property-read Collection<int, Wrestler> $wrestlers
 *
 * @method static \Database\Factories\Users\UserFactory factory($count = null, $state = [])
 * @method static UserBuilder<static>|User newModelQuery()
 * @method static UserBuilder<static>|User newQuery()
 * @method static UserBuilder<static>|User query()
 *
 * @mixin \Eloquent
 */
#[UseFactory(UserFactory::class)]
#[UseEloquentBuilder(UserBuilder::class)]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

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
        'avatar_path',
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

    public function getAvatar(): string
    {
        return $this->avatar_path ?? 'blank.png';
    }

    /**
     * Get the formatted phone number attribute.
     *
     * @return Attribute<string, never>
     */
    public function formattedPhoneNumber(): Attribute
    {
        return new Attribute(
            get: fn () => $this->phone_number
                ? sprintf('(%s) %s-%s', mb_substr($this->phone_number, 0, 3), mb_substr($this->phone_number, 3, 3), mb_substr($this->phone_number, 6, 4))
                : '',
        );
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }

    /**
     * Undocumented function
     *
     * @return HasMany<Wrestler, static>
     */
    public function wrestlers(): HasMany
    {
        return $this->hasMany(Wrestler::class, 'user_id');
    }
}
