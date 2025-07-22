# HasNameSearch Builder Trait

The `HasNameSearch` trait provides reusable name-based search functionality for Laravel Eloquent builders working with models that have `first_name` and `last_name` columns.

## Overview

This trait eliminates code duplication across table components by centralizing intelligent name search logic that handles:
- Case-insensitive exact matching
- Word boundary prefix matching to prevent false positives
- Proper SQL injection protection with parameter binding
- Consistent search behavior across the application

## Usage

### Applying the Trait

```php
use App\Builders\Concerns\HasNameSearch;
use Illuminate\Database\Eloquent\Builder;

class UserBuilder extends Builder
{
    use HasNameSearch;
    
    // Other builder methods...
}
```

### Available Methods

#### `whereNameMatches(string $searchTerm): static`

Searches for exact matches or word-boundary prefix matches on `first_name` and `last_name`.

**Search Logic:**
- Exact match: "John" matches "John Smith"
- Word boundary prefix: "John" matches "John Jr" but NOT "Johnson"
- Case insensitive: "JOHN" matches "john smith"

```php
// Find users with exact or prefix name matches
$users = User::query()
    ->whereNameMatches('John')
    ->get();

// Results: "John Smith", "John Jr", "Jane John-Doe"
// Excludes: "Johnson", "Johnny"
```

#### `whereNameContains(string $searchTerm): static`

Broader LIKE matching for more flexible search results.

```php
// Find users with names containing the term anywhere
$users = User::query()
    ->whereNameContains('ohn')
    ->get();

// Results: "John Smith", "Johnny Cash", "Bob Johnson"
```

### Method Chaining

Both methods return the builder instance for fluent chaining:

```php
$users = User::query()
    ->whereNameMatches('John')
    ->where('status', 'active')
    ->orderBy('last_name')
    ->get();
```

## Implementation Details

### SQL Generation

**SQLite/MySQL Compatible:**
```sql
-- whereNameMatches('John')
WHERE (
    LOWER(first_name) = LOWER('John') OR 
    LOWER(last_name) = LOWER('John') OR 
    LOWER(first_name) LIKE LOWER('John %') OR 
    LOWER(last_name) LIKE LOWER('John %')
)

-- whereNameContains('ohn')  
WHERE (
    LOWER(first_name) LIKE LOWER('%ohn%') OR 
    LOWER(last_name) LIKE LOWER('%ohn%')
)
```

### Security Features

- **Parameter Binding**: All search terms use proper parameter binding to prevent SQL injection
- **Input Sanitization**: Automatically trims whitespace from search terms
- **Case Normalization**: Uses database-level LOWER() for consistent case handling

## Applied To

Currently used by:
- `SingleRosterMemberBuilder` (Managers, Referees, Wrestlers)
- `UserBuilder`
- Table components: Users, Managers (eliminates duplicated search logic)

## Testing

Comprehensive integration tests verify:
- Exact name matching behavior
- Word boundary logic (prevents false positives)
- Case insensitivity
- Special character handling
- Method chaining compatibility
- SQL injection protection

**Test Location:** `tests/Integration/Builders/Concerns/HasNameSearchIntegrationTest.php`

## Benefits

1. **Code Reuse**: Single implementation used across multiple builders
2. **Consistency**: Standardized search behavior application-wide  
3. **Maintainability**: Changes to search logic update all components
4. **Performance**: Database-level filtering with proper indexing support
5. **Security**: Built-in SQL injection protection

## Migration from Manual Search

**Before (duplicated in each component):**
```php
Column::make('Name', 'full_name')
    ->searchable(function ($builder, $searchTerm) {
        $builder->whereRaw('LOWER(first_name) LIKE LOWER(?)', ["%{$searchTerm}%"])
               ->orWhereRaw('LOWER(last_name) LIKE LOWER(?)', ["%{$searchTerm}%"]);
    });
```

**After (using trait):**
```php  
Column::make('Name', 'full_name')
    ->searchable(function ($builder, $searchTerm) {
        $builder->whereNameMatches($searchTerm);
    });
```

## Future Enhancements

Potential additions:
- Fuzzy matching support (Levenshtein distance)
- Full-text search integration
- Nickname/alias matching
- International character normalization

---

*Added in PR #525 - Integration Test Improvements*