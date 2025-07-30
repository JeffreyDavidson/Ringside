# Laravel Conventions

Laravel-specific conventions and patterns for Ringside development.

## Overview

Laravel conventions ensure consistent, framework-compliant code patterns.

## Model Conventions

### Model Standards
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

## Controller Conventions

### Controller Standards
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

## Related Documentation
- [Code Style Guide](code-style.md)
- [PHP Standards](php.md)
- [Database Standards](database.md)
