# Architecture Guide

Comprehensive guide to Ringside's architecture, patterns, and design decisions.

## Domain Structure

The application follows a modular domain-driven approach:

### Core Directories
- **`app/Actions/`** - Business logic operations using Laravel Actions pattern
- **`app/Repositories/`** - Data access layer with interfaces and traits
- **`app/Data/`** - Data transfer objects for type-safe data handling
- **`app/Models/`** - Eloquent models with extensive traits and contracts
- **`app/Livewire/`** - Interactive UI components with standardized base classes
- **`app/Rules/`** - Custom validation rules for business logic

### Supporting Directories
- **`app/Livewire/Concerns/Data/`** - Traits for presenting cached dropdown data in forms
- **`app/Models/Concerns/`** - Reusable model traits and contracts
- **`app/Repositories/Concerns/`** - Shared repository functionality
- **`app/Builders/`** - Custom Eloquent query builders

## Key Architectural Patterns

### Repository Pattern with Traits

Each entity has a repository implementing an interface with common functionality shared via traits:

```php
class WrestlerRepository implements WrestlerRepositoryInterface
{
    use ManagesEmployment, ManagesRetirement, ManagesInjury, ManagesSuspension;
    
    public function __construct(private Wrestler $wrestler) {}
    
    public function create(array $data): Wrestler
    {
        return $this->wrestler->create($data);
    }
}
```

**Key Benefits:**
- **Trait Composition**: `ManagesEmployment<WrestlerEmployment, Wrestler>`
- **Interface Contracts**: Clear API definitions for each repository
- **Shared Logic**: Common employment, retirement, injury patterns
- **Type Safety**: Generic traits with proper type hints

### Laravel Actions Pattern

Business operations encapsulated in action classes using [Lorisleiva\Actions](https://laravelactions.com/):

```php
class EmployWrestlerAction
{
    use AsAction;
    
    public function handle(Wrestler $wrestler, Carbon $startDate): Wrestler
    {
        $this->validateEmployment($wrestler, $startDate);
        
        return $this->wrestlerRepository->createEmployment($wrestler, $startDate);
    }
    
    private function validateEmployment(Wrestler $wrestler, Carbon $startDate): void
    {
        if ($wrestler->isEmployed()) {
            throw CannotBeEmployedException::alreadyEmployed($wrestler);
        }
    }
}
```

**Key Features:**
- **Consistent Structure**: `handle()` method and `AsAction` trait
- **Base Classes**: `BaseWrestlerAction` for shared functionality
- **Complex Operations**: Stable merging/splitting as dedicated Actions
- **Multiple Dispatch**: Jobs, commands, or direct method calls
- **Validation**: Built-in business rule validation with custom exceptions

### Livewire Form Architecture

Standardized form components with comprehensive base class:

```php
abstract class LivewireBaseForm extends Component
{
    // Template method pattern for standardized form lifecycle
    abstract protected function rules(): array;
    abstract protected function getModelData(): array;
    abstract protected function loadExtraData(): void;
    
    public function submit(): void
    {
        $this->validate();
        $this->createOrUpdateModel();
        $this->dispatch('model-updated');
    }
}
```

**Key Benefits:**
- **Template Method Pattern**: Standardized form lifecycle
- **Generic Type Support**: `@template TForm of LivewireBaseForm`
- **Child Implementation**: `rules()`, `getModelData()`, `loadExtraData()`
- **Consistent Behavior**: All forms follow same patterns

### Livewire Directory Structure Standard

**CRITICAL REQUIREMENT**: All model directories in `app/Livewire/` must follow the standardized four-directory structure:

```
app/Livewire/{ModelName}/
├── Components/
│   └── {ModelName}ActionsComponent.php
├── Forms/
│   └── {ModelName}Form.php
├── Modals/
│   └── {ModelName}FormModal.php
└── Tables/
    ├── {ModelName}sTable.php
    └── Previous{RelatedEntity}Table.php (as needed)
```

#### Required Directory Structure

**Every model MUST have these four directories:**

1. **`Components/`** - Business action components
   - Contains `{ModelName}ActionsComponent.php`
   - Handles all business operations (employ, retire, suspend, etc.)
   - Provides reusable action interfaces across different contexts

2. **`Forms/`** - Form handling components  
   - Contains `{ModelName}Form.php` extending `LivewireBaseForm`
   - Handles data validation, transformation, and persistence
   - Implements abstract methods: `rules()`, `getModelData()`, `loadExtraData()`

3. **`Modals/`** - Modal interface components
   - Contains `{ModelName}FormModal.php` extending `BaseFormModal`
   - Provides modal-based form interfaces
   - Includes dummy data generation for development/testing

4. **`Tables/`** - Data display and management tables
   - Contains `{ModelName}sTable.php` extending `BaseTableWithActions`
   - May contain `Previous{RelatedEntity}Table.php` for relationship history
   - Integrates with Laravel Livewire Tables for filtering and pagination

#### ActionsComponent Standard

**Every model MUST have an ActionsComponent** that follows this pattern:

```php
class {ModelName}ActionsComponent extends Component
{
    public {ModelName} ${modelName};
    
    public function mount({ModelName} ${modelName}): void
    {
        $this->{modelName} = ${modelName};
    }
    
    // Business action methods with consistent patterns:
    public function employ(): void { /* Gate + Action + Event + Flash */ }
    public function release(): void { /* Gate + Action + Event + Flash */ }
    public function retire(): void { /* Gate + Action + Event + Flash */ }
    // ... other business actions as applicable
    
    public function render(): View
    {
        return view('livewire.{modelName}.components.{modelName}-actions-component');
    }
}
```

#### Current Implementation Status

✅ **Complete Structure**: Managers, Referees, Titles, Wrestlers  
❌ **Missing Components**: Events, Matches, Stables, TagTeams, Users, Venues

**Required Actions for Consistency:**
1. Create missing `Components/` directories
2. Create missing `{ModelName}ActionsComponent.php` files
3. Ensure all ActionsComponents follow the standard pattern
4. Update corresponding Unit tests to cover all directories

#### Benefits of Standardized Structure

- **Consistent Development**: All models follow identical patterns
- **Predictable Organization**: Developers know exactly where to find components
- **Complete Testing Coverage**: Unit tests can be systematically applied
- **Maintainable Codebase**: Changes follow established conventions
- **Scalable Architecture**: New models automatically follow proven patterns

### Time-Based Entity Management

Complex status tracking with activity periods:

```php
trait ManagesEmployment
{
    public function createEmployment(Model $entity, Carbon $startDate): Model
    {
        $this->endCurrentEmployment($entity);
        
        $entity->employments()->create([
            'started_at' => $startDate,
            'ended_at' => null,
        ]);
        
        return $entity->fresh();
    }
}
```

**Key Features:**
- **Activity Periods**: Start/end dates for all status changes
- **Status Transitions**: Automatic handling of overlapping periods
- **History Tracking**: Complete audit trail of all status changes
- **Business Rules**: Validation of status transition logic

## Model Relationships

The application handles complex wrestling industry relationships:

### Entity Relationships
- **Wrestlers**: Can join/leave tag teams and stables
- **Employment**: Tracking with start/end dates
- **Status Management**: Injury, suspension, retirement periods
- **Championships**: Title reigns with activity periods
- **Events**: Matches with multiple participants and titles

### Relationship Patterns
```php
// Polymorphic relationships
public function champion(): MorphTo
{
    return $this->morphTo();
}

// Time-based relationships
public function currentEmployment(): HasOne
{
    return $this->hasOne(WrestlerEmployment::class)
        ->whereNull('ended_at');
}

// Complex queries with builders
public function bookableWrestlers(): Builder
{
    return $this->wrestlers()
        ->employed()
        ->notInjured()
        ->notSuspended()
        ->notRetired();
}
```

## Model Attribute Patterns

### Naming Conventions
- **Individual People**: Managers and Referees use `first_name` and `last_name` fields
- **Entity Models**: Wrestlers, TagTeams, Stables, Events, Venues, and Titles use single `name` field
- **Address Models**: Venues use structured address fields (`street_address`, `city`, `state`, `zipcode`)

### Attribute Casting
```php
protected $casts = [
    'status' => EmploymentStatus::class,
    'started_at' => 'datetime',
    'ended_at' => 'datetime',
    'height' => HeightValueObject::class,
    'weight' => 'integer',
];
```

## Business Rules

### Capability Matrix

| Capability | Wrestlers | Managers | Referees | TagTeams | Stables | Titles |
|------------|-----------|----------|----------|----------|---------|--------|
| **Injury** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Suspension** | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| **Retirement** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Employment** | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| **Pull** | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **Debut** | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **Booking** | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ |

### Critical Business Rules

1. **Employment Dependency**: Injury/suspension only possible when currently employed
2. **Release-to-Retirement**: Released entities CAN be retired (valid workflow)
3. **Stable Membership**: Only Wrestlers and TagTeams can be direct stable members
4. **Manager Association**: Managers associate with stables through wrestlers/tag teams they manage
5. **Repository Methods**: Not all repositories have all methods (capability-based)

### Employment Status Dependencies
- **Critical Rule**: Injury/suspension should only be possible when entity is currently employed
- **Non-Employed States**: Retired, released, unemployed, or future scheduled employment
- **Status Validation**: Always validate employment status before applying injury or suspension

## Key Traits and Contracts

### Status Management Interfaces
- **`HasActivityPeriods`** - Entities with activation/deactivation
- **`IsEmployable`** - Employment status management
- **`IsRetirable`** - Retirement status tracking
- **`IsInjurable`** - Injury status management
- **`IsSuspendable`** - Suspension status management

### Relationship Management Interfaces
- **`CanJoinStables/TagTeams`** - Membership management
- **`CanBeManaged`** - Manager relationships
- **`HasChampionships`** - Title-holding entities
- **`HasTagTeamWrestlers`** - Tag team wrestler partners
- **`Manageable`** - Entities that can be managed

### Strategic Interfaces (2024)
- **`Validatable`** - Business operation validation
- **`HasStableMembership`** - Stable membership capability
- **`HasEmploymentHistory`** - Employment history tracking
- **`ProvidesCurrentWrestlers`** - Marker interface for wrestler access
- **`ProvidesCurrentTagTeams`** - Marker interface for tag team access

## Interface-Based Architecture

### Replacing method_exists() with Type-Safe Interfaces

The codebase uses type-safe interface contracts instead of runtime method checking:

```php
// ✅ CORRECT - Use type-safe interface checking
if ($entity instanceof ProvidesCurrentWrestlers) {
    $wrestlers = $entity->currentWrestlers()->get();
}

// ❌ AVOID - Runtime method checking
if (method_exists($entity, 'currentWrestlers')) {
    $wrestlers = $entity->currentWrestlers()->get();
}
```

### Interface Design Principles

1. **Marker Interfaces**: `ProvidesCurrentWrestlers` for simple method access
2. **Comprehensive Interfaces**: `Validatable` for full behavioral contracts
3. **Domain-Specific**: `HasTagTeamWrestlers` over overly generic interfaces
4. **Naming Conventions**: `Has*`, `Can*`, `Is*`, `Provides*` prefixes

## Custom Exception Integration

Domain-specific exceptions for clear error messaging:

```php
class CannotBeEmployedException extends Exception
{
    public static function alreadyEmployed(Model $entity): self
    {
        return new self("Cannot employ {$entity->name} - already employed.");
    }
    
    public static function isRetired(Model $entity): self
    {
        return new self("Cannot employ {$entity->name} - currently retired.");
    }
}
```

## Stable Membership Architecture

### Polymorphic Approach
Uses polymorphic `StableMember` model with `stables_members` table:

```php
// Database structure
Schema::create('stables_members', function (Blueprint $table) {
    $table->id();
    $table->foreignId('stable_id');
    $table->morphs('member'); // member_type, member_id
    $table->timestamp('joined_at');
    $table->timestamp('left_at')->nullable();
});

// Morph map configuration
Relation::morphMap([
    'wrestler' => Wrestler::class,
    'tagTeam' => TagTeam::class,
]);
```

### Benefits
- **Consolidated Model**: Single `StableMember` instead of three separate models
- **Better Maintainability**: One relationship pattern to manage
- **Type Safety**: Proper morph map configuration

## Code Quality Standards

### PHP Standards
- **Strict Types**: `declare(strict_types=1);` in all files
- **Laravel Pint**: Custom rules in `pint.json`
- **PHPStan Level 6**: 100% type coverage requirement
- **Comprehensive PHPDoc**: Annotations with generics

### Architecture Standards
- **Import Classes**: Always import instead of using FQCN in docblocks
- **Avoid `method_exists()`**: Use type-safe interfaces and contracts
- **Modern Laravel**: Use attributes style for accessors/mutators
- **Validation Placement**: `ensureCanBeXXXX()` methods belong in Actions, not Models

## Architecture Concerns & Future Refactoring

### Over-Refactored Components
Some validation classes in `Models\Concerns\Validates*` may be over-refactored and could benefit from consolidation.

### Builder Pattern Considerations
The bookable scope implementation needs entity-specific requirements handling with interface-based approaches.

### Policy Simplification Strategy
Current policies are over-engineered. Recommended approach:
1. Separate user permissions from model state validation
2. Use Laravel before hooks for simple admin checks
3. Extract model logic to dedicated locations
4. Use custom exceptions for business rule violations

## Future Considerations

### Type Safety Improvements
Consider creating a `RosterMemberType` enum for better type safety:

```php
enum RosterMemberType: string
{
    case Wrestler = 'wrestler';
    case Manager = 'manager';
    case Referee = 'referee';
    case TagTeam = 'tag_team';
}
```

### Architectural Decision Process
1. **Pause Implementation**: Stop and assess architectural impact
2. **Document Questions**: Articulate concerns and alternatives
3. **Consult Stakeholders**: Collaborative discussion
4. **Joint Decision**: Work together on best approach
5. **Update Documentation**: Document decisions and reasoning