# Variable Naming Conventions

Variable naming patterns and conventions for Ringside development.

## Overview

Clear variable naming ensures readable, maintainable code.

## Variable Naming

### Naming Guidelines
- **Descriptive Names**: Use clear, descriptive variable names
- **Camel Case**: Use camelCase for variables and properties
- **Avoid Abbreviations**: Use full words instead of abbreviations
- **Context Appropriate**: Names should reflect the variable's purpose

```php
// ✅ CORRECT - Clear, descriptive names
$wrestler = Wrestler::factory()->create();
$employmentDate = now()->subDays(30);
$currentChampionship = $title->currentChampionship();

// ❌ INCORRECT - Unclear or abbreviated names
$w = Wrestler::factory()->create();
$empDate = now()->subDays(30);
$cc = $title->currentChampionship();
```

## Related Documentation
- [Naming Conventions](naming.md)
- [PHP Standards](../php.md)
- [Code Style Guide](../code-style.md)
