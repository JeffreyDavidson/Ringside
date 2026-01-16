# Technical Specification: Promotions System

> Reference: @.agent-os/specs/2026-01-16-promotions-system/spec.md

---

## Database Schema

### Promotions Table

```php
Schema::create('promotions', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->string('slug')->unique();
    $table->json('settings')->nullable();
    $table->timestamps();

    $table->index('user_id');
    $table->index('slug');
});
```

### Entity Foreign Keys

All promotion-owned entities receive:

```php
// Added to each entity table migration
$table->foreignUlid('promotion_id')->constrained()->cascadeOnDelete();
$table->index('promotion_id');
```

**Affected Tables:**
- wrestlers
- tag_teams
- managers
- referees
- stables
- events
- venues
- titles

---

## Models

### Promotion Model

**Location:** `app/Models/Promotion.php`

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function wrestlers(): HasMany
    {
        return $this->hasMany(Wrestler::class);
    }

    public function tagTeams(): HasMany
    {
        return $this->hasMany(TagTeam::class);
    }

    public function managers(): HasMany
    {
        return $this->hasMany(Manager::class);
    }

    public function referees(): HasMany
    {
        return $this->hasMany(Referee::class);
    }

    public function stables(): HasMany
    {
        return $this->hasMany(Stable::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }

    public function titles(): HasMany
    {
        return $this->hasMany(Title::class);
    }
}
```

### BelongsToPromotion Trait

**Location:** `app/Models/Concerns/BelongsToPromotion.php`

```php
<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Promotion;
use App\Models\Scopes\PromotionScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToPromotion
{
    public static function bootBelongsToPromotion(): void
    {
        static::addGlobalScope(new PromotionScope());

        static::creating(function ($model) {
            if (empty($model->promotion_id)) {
                $model->promotion_id = current_promotion_id();
            }
        });
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }
}
```

### PromotionScope Global Scope

**Location:** `app/Models/Scopes/PromotionScope.php`

```php
<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class PromotionScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if ($promotionId = current_promotion_id()) {
            $builder->where($model->getTable().'.promotion_id', $promotionId);
        }
    }
}
```

---

## Promotion Context

### Helper Function

**Location:** `app/helpers.php`

```php
<?php

declare(strict_types=1);

if (! function_exists('current_promotion_id')) {
    function current_promotion_id(): ?string
    {
        return session('current_promotion_id');
    }
}

if (! function_exists('current_promotion')) {
    function current_promotion(): ?\App\Models\Promotion
    {
        if ($id = current_promotion_id()) {
            return \App\Models\Promotion::find($id);
        }
        return null;
    }
}

if (! function_exists('set_current_promotion')) {
    function set_current_promotion(\App\Models\Promotion|string $promotion): void
    {
        $id = $promotion instanceof \App\Models\Promotion ? $promotion->id : $promotion;
        session(['current_promotion_id' => $id]);
    }
}
```

### Middleware

**Location:** `app/Http/Middleware/EnsurePromotionContext.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePromotionContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! current_promotion_id()) {
            // Set default promotion (first owned, or most recently used)
            $promotion = $user->promotions()->first();

            if ($promotion) {
                set_current_promotion($promotion);
            }
        }

        return $next($request);
    }
}
```

---

## User Model Changes

### Updated Relationships

```php
// User model - REMOVE this relationship:
// public function wrestlers(): HasMany

// User model - ADD these relationships:
public function promotions(): HasMany
{
    return $this->hasMany(Promotion::class);
}

public function currentPromotion(): ?Promotion
{
    return current_promotion();
}
```

---

## Promotion Settings Schema

```php
// Default settings structure
[
    'timezone' => 'America/New_York',
    'currency' => 'USD',
    'date_format' => 'M j, Y',
    'time_format' => 'g:i A',
    'default_match_duration' => 15, // minutes
    'roster_display' => 'grid', // grid|list
]
```

---

## Testing Patterns

### Promotion Factory

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PromotionFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->company() . ' Wrestling';

        return [
            'user_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'settings' => null,
        ];
    }

    public function withSettings(array $settings): static
    {
        return $this->state(fn (array $attributes) => [
            'settings' => $settings,
        ]);
    }
}
```

### Test Helpers

```php
// In test setup
protected function actingAsPromoter(User $user = null, Promotion $promotion = null): self
{
    $user ??= User::factory()->create();
    $promotion ??= Promotion::factory()->for($user, 'owner')->create();

    set_current_promotion($promotion);

    return $this->actingAs($user);
}
```

### Scoping Tests

```php
it('scopes wrestlers to current promotion', function () {
    $promotion1 = Promotion::factory()->create();
    $promotion2 = Promotion::factory()->create();

    $wrestler1 = Wrestler::factory()->for($promotion1)->create();
    $wrestler2 = Wrestler::factory()->for($promotion2)->create();

    set_current_promotion($promotion1);

    expect(Wrestler::all())->toHaveCount(1)
        ->and(Wrestler::first()->id)->toBe($wrestler1->id);
});
```

---

## Migration Order

1. Create promotions table
2. Add promotion_id to all entity tables
3. Backfill existing data (if needed)
4. Add NOT NULL constraint after backfill

---

## File Locations Summary

| File | Purpose |
|------|---------|
| `app/Models/Promotion.php` | Promotion model |
| `app/Models/Concerns/BelongsToPromotion.php` | Trait for owned entities |
| `app/Models/Scopes/PromotionScope.php` | Global scope for filtering |
| `app/Http/Middleware/EnsurePromotionContext.php` | Context middleware |
| `app/helpers.php` | Helper functions |
| `database/factories/PromotionFactory.php` | Test factory |
| `database/migrations/*_create_promotions_table.php` | Schema migration |
| `database/migrations/*_add_promotion_id_to_entities.php` | FK migrations |
| `tests/Unit/Models/PromotionTest.php` | Unit tests |
| `tests/Feature/PromotionScopingTest.php` | Integration tests |
