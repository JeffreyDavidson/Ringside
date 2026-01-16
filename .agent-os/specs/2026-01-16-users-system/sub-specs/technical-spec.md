# Technical Specification: Users System

> Reference: @.agent-os/specs/2026-01-16-users-system/spec.md

---

## Database Schema

### Users Table Updates

```php
// Migration to add role and status columns
Schema::table('users', function (Blueprint $table) {
    $table->string('role')->default('promoter')->after('password');
    $table->string('status')->default('active')->after('role');

    $table->index('role');
    $table->index('status');
});
```

**Final Users Table Structure:**
| Column | Type | Description |
|--------|------|-------------|
| id | ulid | Primary key |
| name | string | Display name |
| email | string | Unique email address |
| email_verified_at | timestamp | Email verification |
| password | string | Bcrypt hashed |
| role | string | Role enum value |
| status | string | UserStatus enum value |
| remember_token | string | Session persistence |
| created_at | timestamp | Creation time |
| updated_at | timestamp | Last update |

---

## Enums

### Role Enum

**Location:** `app/Enums/Role.php`

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Promoter = 'promoter';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Promoter => 'Promoter',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }
}
```

### UserStatus Enum

**Location:** `app/Enums/UserStatus.php`

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Pending = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Pending => 'Pending Verification',
        };
    }

    public function canLogin(): bool
    {
        return $this === self::Active;
    }
}
```

---

## Models

### User Model

**Location:** `app/Models/User.php`

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Role;
use App\Enums\UserStatus;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory;
    use HasUlids;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'status' => UserStatus::class,
        ];
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the promotions owned by this user.
     */
    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    // ==================== ACCESSORS ====================

    /**
     * Get the user's current/default promotion.
     */
    public function currentPromotion(): ?Promotion
    {
        return current_promotion();
    }

    // ==================== QUERY HELPERS ====================

    /**
     * Check if user is an administrator.
     */
    public function isAdmin(): bool
    {
        return $this->role->isAdmin();
    }

    /**
     * Check if user can log in.
     */
    public function canLogin(): bool
    {
        return $this->status->canLogin();
    }
}
```

### Relationship Changes

**IMPORTANT:** Remove the following from User model if it exists:

```php
// REMOVE THIS RELATIONSHIP
public function wrestlers(): HasMany
{
    return $this->hasMany(Wrestler::class);
}
```

Users no longer directly own Wrestlers. The ownership hierarchy is:

```
User → Promotion → Wrestler
```

---

## Authentication

### Login Validation

Update login logic to check user status:

**Location:** `app/Http/Requests/Auth/LoginRequest.php` or similar

```php
public function authenticate(): void
{
    $this->ensureIsNotRateLimited();

    $user = User::where('email', $this->email)->first();

    if ($user && ! $user->canLogin()) {
        throw ValidationException::withMessages([
            'email' => __('Your account is not active.'),
        ]);
    }

    if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
        RateLimiter::hit($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.failed'),
        ]);
    }

    RateLimiter::clear($this->throttleKey());
}
```

---

## Authorization

### Gates

**Location:** `app/Providers/AppServiceProvider.php` or `AuthServiceProvider`

```php
use App\Models\User;
use Illuminate\Support\Facades\Gate;

// In boot() method:
Gate::define('admin', function (User $user) {
    return $user->isAdmin();
});

Gate::define('manage-promotion', function (User $user, Promotion $promotion) {
    return $user->id === $promotion->user_id;
});
```

---

## Testing Patterns

### User Factory

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Role;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => Role::Promoter,
            'status' => UserStatus::Active,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::Admin,
        ]);
    }

    public function promoter(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::Promoter,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::Suspended,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::Pending,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
```

### Test Examples

```php
it('can create a promotion', function () {
    $user = User::factory()->create();

    $promotion = $user->promotions()->create([
        'name' => 'Test Wrestling',
        'slug' => 'test-wrestling',
    ]);

    expect($user->promotions)->toHaveCount(1)
        ->and($promotion->owner->id)->toBe($user->id);
});

it('prevents suspended users from logging in', function () {
    $user = User::factory()->suspended()->create();

    expect($user->canLogin())->toBeFalse();
});

it('does not have direct wrestler relationship', function () {
    $user = User::factory()->create();

    expect(method_exists($user, 'wrestlers'))->toBeFalse();
});
```

---

## Migration Checklist

1. Add role and status columns to users table
2. Create Role enum
3. Create UserStatus enum
4. Update User model with new casts and relationships
5. Remove wrestlers() relationship from User model
6. Update UserFactory with role/status states
7. Update login logic for status checking
8. Add authorization gates

---

## File Locations Summary

| File | Purpose |
|------|---------|
| `app/Models/User.php` | User model (updated) |
| `app/Enums/Role.php` | Role enum |
| `app/Enums/UserStatus.php` | User status enum |
| `database/migrations/*_add_role_status_to_users_table.php` | Schema migration |
| `database/factories/UserFactory.php` | Factory (updated) |
| `tests/Unit/Models/UserTest.php` | Unit tests |
| `tests/Feature/Auth/AuthenticationTest.php` | Auth tests |
