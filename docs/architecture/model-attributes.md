# Model Attributes

How Ringside models declare their attributes. The enforced rules are in `.ai/rules/models.md` and `.ai/rules/models-value-objects-casts.md`; this page shows the shape.

## Declaration

Attributes go on the class, not in properties. A model lists its mass-assignable fields with `#[Fillable(...)]`, hides sensitive ones with `#[Hidden(...)]`, and binds its factory and builder with `#[UseFactory]` and `#[UseEloquentBuilder]`. Casts come from a `casts()` method. Column types are documented with `@property` lines in the class docblock, not with typed public properties.

```php
/**
 * @property int $id
 * @property string $name
 * @property Height $height
 * @property Weight $weight
 * @property EmploymentStatus $status
 */
#[Fillable('name', 'height', 'weight', 'hometown', 'signature_move')]
#[Appends('status')]
#[UseFactory(WrestlerFactory::class)]
#[UseEloquentBuilder(WrestlerBuilder::class)]
class Wrestler extends Model
{
    protected function casts(): array
    {
        return [
            'height' => Height::class,
            'weight' => Weight::class,
        ];
    }
}
```

## Casts and value objects

- Enum-backed columns are cast to their enum (`'role' => MembershipRole::class`).
- A value with its own rules is a value object in `app/ValueObjects` with a cast in `app/Casts` (`Height`, `Weight`, `Address`, `PhoneNumber`). `UsdAmount` formats money in ViewModels.
- Status is computed, not stored, on wrestlers, managers, referees, tag teams, stables and titles (see [Core Patterns](core-patterns.md)).

## Privileged fields are not fillable

`role`, `status`, tenant and ownership keys (`promotion_id`, `user_id`), versions and similar fields stay out of `#[Fillable]`. The code that sets one assigns it explicitly, through `forceFill()` or through the relationship that owns the key. The one exception is the `PromotionMembership` pivot, which keeps `role` and `status` fillable because `attach()` and `updateExistingPivot()` fill the pivot class. See `.ai/rules/models.md`.

## Related documentation

- [Core Patterns](core-patterns.md)
- [Enums](enums.md)
- [Laravel Conventions](../guidelines/laravel.md)
- [Database Standards](../guidelines/database.md)
