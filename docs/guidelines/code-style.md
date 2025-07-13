# Code Style Guide

PHP coding standards and conventions for Ringside development.

## Overview

Ringside follows strict coding standards to ensure consistent, maintainable, and high-quality code across the entire application.

## PHP Standards

### Basic Requirements
- **PHP Version**: 8.1 or higher
- **Strict Types**: `declare(strict_types=1);` in all PHP files
- **PSR-12**: PHP coding standards compliance
- **Laravel Conventions**: Follow Laravel framework best practices

### File Structure
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

### Import Standards
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

### Type Declarations
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

## Documentation Standards

### PHPDoc Requirements
- **Class Documentation**: All classes must have comprehensive PHPDoc
- **Method Documentation**: All public methods must be documented
- **Property Documentation**: Use `@property` tags for dynamic properties
- **Generic Types**: Use proper generic type annotations

```php
/**
 * Repository for managing wrestler employment operations.
 *
 * @template T of Wrestler
 */
class WrestlerRepository
{
    /**
     * Create new employment record for wrestler.
     *
     * @param Wrestler $wrestler The wrestler to employ
     * @param Carbon $startDate Employment start date
     * @return WrestlerEmployment Created employment record
     * @throws CannotBeEmployedException If wrestler cannot be employed
     */
    public function createEmployment(Wrestler $wrestler, Carbon $startDate): WrestlerEmployment
    {
        // Implementation
    }
}
```

### Comment Standards
- **Avoid Obvious Comments**: Don't comment what the code clearly shows
- **Explain Why**: Comments should explain reasoning, not what
- **Complex Logic**: Comment complex business logic and algorithms
- **TODO Comments**: Use TODO for future improvements

```php
// ✅ CORRECT - Explains business reasoning
// Released entities CAN be retired per business workflow
if ($wrestler->isReleased()) {
    return true;
}

// ❌ INCORRECT - States the obvious
// Set the name to the provided name
$wrestler->name = $name;
```

## Code Quality Tools

### Laravel Pint
- **Configuration**: Custom rules defined in `pint.json`
- **Usage**: `composer lint` to fix formatting
- **CI Integration**: Automated formatting checks

### PHPStan
- **Level**: Level 6 static analysis
- **Coverage**: 100% type coverage requirement
- **Configuration**: Rules defined in `phpstan.neon`
- **Usage**: `composer test:types` for analysis

### Rector
- **Modernization**: Automated code modernization
- **Usage**: `composer rector` to apply updates
- **Configuration**: Rules defined in `rector.php`

## Architecture Standards

### Class Design
- **Single Responsibility**: Each class should have one clear purpose
- **Interface Segregation**: Use focused interfaces instead of large ones
- **Dependency Injection**: Use constructor injection for dependencies
- **Immutability**: Prefer immutable objects where possible

```php
// ✅ CORRECT - Single responsibility with interface
class WrestlerEmploymentAction implements EmploymentActionInterface
{
    public function __construct(
        private WrestlerRepository $repository,
        private EmploymentValidator $validator
    ) {}

    public function handle(Wrestler $wrestler, Carbon $date): WrestlerEmployment
    {
        $this->validator->validate($wrestler, $date);
        return $this->repository->createEmployment($wrestler, $date);
    }
}
```

### Method Design
- **Small Methods**: Keep methods focused and concise
- **Clear Names**: Method names should describe their purpose
- **Parameter Limits**: Maximum 3-4 parameters per method
- **Return Types**: Always specify return types

```php
// ✅ CORRECT - Focused method with clear purpose
public function isBookableOn(Carbon $date): bool
{
    return $this->isEmployed() 
        && !$this->isInjured() 
        && !$this->isSuspended()
        && $this->isAvailableOn($date);
}

// ❌ INCORRECT - Too many responsibilities
public function processWrestlerStatusAndBooking($wrestler, $date, $match, $title)
{
    // Complex logic handling multiple concerns
}
```

## Error Handling

### Exception Standards
- **Custom Exceptions**: Use domain-specific exceptions
- **Clear Messages**: Exception messages should be user-friendly
- **Static Factories**: Use static factory methods for consistency
- **Exception Hierarchy**: Organize exceptions by domain

```php
// ✅ CORRECT - Domain-specific exception with static factory
class CannotBeEmployedException extends Exception
{
    public static function alreadyEmployed(Wrestler $wrestler): self
    {
        return new self("Cannot employ {$wrestler->name} - already employed.");
    }
    
    public static function isRetired(Wrestler $wrestler): self
    {
        return new self("Cannot employ {$wrestler->name} - currently retired.");
    }
}
```

### Error Handling Patterns
- **Fail Fast**: Validate inputs early and fail fast
- **Meaningful Messages**: Provide actionable error messages
- **Logging**: Log errors appropriately for debugging
- **Recovery**: Provide recovery mechanisms where possible

## Testing Standards

### Test Code Style
- **Import Classes**: Always import test classes, never use FQCN
- **Clear Structure**: Use AAA pattern with proper separation
- **Descriptive Names**: Test names should explain expected behavior
- **Consistent Formatting**: Follow same formatting rules as application code

```php
// ✅ CORRECT - Proper test structure
use App\Models\Wrestlers\Wrestler;
use App\Actions\Wrestlers\EmployAction;

test('can employ wrestler with valid data', function () {
    // Arrange
    $wrestler = Wrestler::factory()->create();
    $employmentDate = now()->subDays(30);
    
    // Act
    $result = EmployAction::run($wrestler, $employmentDate);
    
    // Assert
    expect($result)->toBeInstanceOf(WrestlerEmployment::class);
    expect($wrestler->fresh()->isEmployed())->toBeTrue();
});
```

## Laravel Conventions

### Model Conventions
- **Naming**: Singular, PascalCase (e.g., `Wrestler`, `TagTeam`)
- **Relationships**: Use descriptive relationship names
- **Attributes**: Use Laravel's modern attribute casting
- **Scopes**: Use clear, descriptive scope names

```php
// ✅ CORRECT - Modern Laravel model
class Wrestler extends Model
{
    use HasFactory;
    
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
    
    public function currentEmployment(): HasOne
    {
        return $this->hasOne(WrestlerEmployment::class)
            ->whereNull('ended_at');
    }
}
```

### Controller Conventions
- **Thin Controllers**: Keep controllers focused on HTTP concerns
- **Resource Controllers**: Use resource controller patterns
- **Form Requests**: Use form request classes for validation
- **Response Formats**: Consistent response formats

```php
// ✅ CORRECT - Thin controller with proper structure
class WrestlersController extends Controller
{
    public function __construct(
        private WrestlerRepository $repository
    ) {}
    
    public function index(): View
    {
        $this->authorize('viewList', Wrestler::class);
        
        return view('wrestlers.index');
    }
    
    public function show(Wrestler $wrestler): View
    {
        $this->authorize('view', $wrestler);
        
        return view('wrestlers.show', compact('wrestler'));
    }
}
```

## Database Standards

### Migration Standards
- **Descriptive Names**: Clear migration file names
- **Atomic Changes**: One logical change per migration
- **Rollback Support**: Always include down() method
- **Foreign Keys**: Use proper foreign key constraints

```php
// ✅ CORRECT - Clear migration structure
public function up(): void
{
    Schema::create('wrestler_employments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('wrestler_id')->constrained()->cascadeOnDelete();
        $table->timestamp('started_at');
        $table->timestamp('ended_at')->nullable();
        $table->timestamps();
        
        $table->index(['wrestler_id', 'ended_at']);
    });
}

public function down(): void
{
    Schema::dropIfExists('wrestler_employments');
}
```

### Query Standards
- **Eloquent Preferred**: Use Eloquent over raw SQL when possible
- **Query Builders**: Use query builders for complex queries
- **Eager Loading**: Prevent N+1 queries with proper eager loading
- **Indexes**: Add appropriate database indexes

```php
// ✅ CORRECT - Proper query with eager loading
public function getActiveWrestlersWithEmployment(): Collection
{
    return Wrestler::query()
        ->with(['currentEmployment', 'currentChampionships'])
        ->employed()
        ->notRetired()
        ->get();
}
```

## Performance Standards

### Code Performance
- **Database Queries**: Minimize and optimize database queries
- **Caching**: Use appropriate caching strategies
- **Memory Usage**: Monitor memory consumption
- **Algorithm Efficiency**: Use efficient algorithms and data structures

### Development Performance
- **Fast Tests**: Keep test execution under 30 seconds
- **Quick Feedback**: Optimize development workflow
- **Parallel Processing**: Use parallel execution where possible
- **Incremental Builds**: Optimize build and deployment processes

## Security Standards

### Input Validation
- **Validate All Input**: Never trust user input
- **Type Checking**: Use proper type checking
- **Sanitization**: Sanitize output appropriately
- **Mass Assignment**: Use proper fillable/guarded properties

### Authentication & Authorization
- **Authentication**: Use Laravel's authentication system
- **Authorization**: Implement proper authorization policies
- **CSRF Protection**: Use CSRF protection for forms
- **Session Security**: Secure session management

## Maintenance Standards

### Code Maintenance
- **Regular Updates**: Keep dependencies current
- **Refactoring**: Regular code refactoring for maintainability
- **Documentation**: Keep documentation current with code
- **Deprecation**: Handle deprecated features properly

### Quality Assurance
- **Code Reviews**: Mandatory code reviews for all changes
- **Testing**: Comprehensive testing at all levels
- **Static Analysis**: Regular static analysis
- **Performance Monitoring**: Monitor application performance

## Enforcement

### Automated Checks
- **Pre-commit Hooks**: Run quality checks before commits
- **CI/CD Pipeline**: Automated quality checks in CI
- **Code Review**: Manual code review process
- **Quality Gates**: Quality gates for deployment

### Tools Integration
```bash
# Code quality checks
composer lint          # Format code
composer test:types     # Static analysis
composer test:coverage  # Test coverage
composer rector         # Code modernization
```

### Quality Metrics
- **Code Coverage**: 100% test coverage required
- **Type Coverage**: 100% type coverage required
- **Static Analysis**: PHPStan level 6 compliance
- **Code Style**: Laravel Pint compliance

This comprehensive code style guide ensures consistent, maintainable, and high-quality code across the entire Ringside application.