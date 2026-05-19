# PHP Standards

PHP coding standards and conventions for Ringside development.

## Overview

Ringside follows strict coding standards to ensure consistent, maintainable, and high-quality code across the entire application.

## Basic Requirements

### PHP Version
- **PHP Version**: 8.1 or higher
- **Strict Types**: `declare(strict_types=1);` in all PHP files
- **PSR-12**: PHP coding standards compliance
- **Laravel Conventions**: Follow Laravel framework best practices

## File Structure

### Standard PHP File Layout
```php
<?php

declare(strict_types=1);

namespace App\Models\Wrestlers;

use App\Models\Concerns\IsEmployable;
use App\Models\Concerns\IsRetirable;
use Illuminate\Database\Eloquent\Model;

/**
 * Wrestler model for managing wrestler entities.
 *
 * @property string $name
 * @property string $hometown
 * @property HeightValueObject $height
 * @property int $weight
 */
class Wrestler extends Model
{
    use IsEmployable, IsRetirable;

    // Class implementation
}
```

## Import Standards

### Class Import Rules
- **Always Import Classes**: Never use Fully Qualified Class Names (FQCN) in code
- **Group Imports**: Organize imports by type (Models, Actions, etc.)
- **Alphabetical Order**: Sort imports alphabetically within groups

```php
// ✅ CORRECT - Import classes
use App\Models\Wrestlers\Wrestler;
use App\Actions\Wrestlers\EmployAction;
use App\Exceptions\CannotBeEmployedException;

// ❌ INCORRECT - Using FQCN
$wrestler = new \App\Models\Wrestlers\Wrestler();
```

## Type Declarations

### Type Safety Requirements
- **Strict Types**: Use strict type declarations everywhere
- **Return Types**: All methods must have return type declarations
- **Parameter Types**: All parameters must have type declarations
- **Property Types**: All properties must have type declarations

```php
// ✅ CORRECT - Proper type declarations
public function createEmployment(Wrestler $wrestler, Carbon $date): WrestlerEmployment
{
    return $wrestler->employments()->create([
        'started_at' => $date,
        'ended_at' => null,
    ]);
}

// ❌ INCORRECT - Missing type declarations
public function createEmployment($wrestler, $date)
{
    return $wrestler->employments()->create([
        'started_at' => $date,
        'ended_at' => null,
    ]);
}
```

## Related Documentation
- [Code Style Guide](code-style.md)
- [Laravel Conventions](laravel.md)
- [Testing Standards](testing.md)
