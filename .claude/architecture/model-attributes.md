# Model Attributes

Model attribute conventions and standards for Ringside development.

## Overview

Model attributes define the data structure and behavior of domain entities.

## Attribute Standards

### Attribute Conventions
- **Type Declarations**: All attributes must have proper type declarations
- **Casting**: Use Laravel's attribute casting for complex types
- **Validation**: Implement proper validation rules
- **Documentation**: Document all attributes with PHPDoc

```php
// ✅ CORRECT - Proper attribute structure
class Wrestler extends Model
{
    protected $fillable = [
        'name',
        'hometown',
        'height',
        'weight',
    ];

    protected $casts = [
        'height' => HeightValueObject::class,
        'weight' => 'integer',
        'started_at' => 'datetime',
    ];

    /**
     * The wrestler's name.
     */
    public string $name;

    /**
     * The wrestler's hometown.
     */
    public string $hometown;
}
```

## Related Documentation
- [Laravel Conventions](../guidelines/laravel.md)
- [PHP Standards](../guidelines/php.md)
- [Database Standards](../guidelines/database.md)
