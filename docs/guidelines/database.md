# Database Standards

Migration standards and database conventions for Ringside development.

## Overview

Database standards ensure consistent, maintainable database schemas and queries.

## Migration Standards

### Migration Guidelines
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

## Query Standards

### Query Guidelines
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

## Related Documentation
- [Code Style Guide](code-style.md)
- [Laravel Conventions](laravel.md)
- [Performance Standards](performance.md)
