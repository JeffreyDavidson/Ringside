# Livewire Component Architecture Standards

## Component Standardization

### ✅ **COMPLETED - Phase 5: Component Standardization**

Successfully implemented standardized naming conventions across all Livewire components:

### Implemented Changes:
- **✅ Actions Components**: Renamed `ActionsComponent.php` → `Actions.php` across all domains
- **✅ Form Components**: Renamed `EventMatchForm.php` → `CreateEditForm.php` for consistency
- **✅ Table Components**: Renamed `{Entity}Table.php` → `Main.php` for primary entity tables
- **✅ Relationship Tables**: Renamed `Previous{Entity}Table.php` → `Previous{Entity}.php`
- **✅ Test Files**: Updated all test files to match new component names
- **✅ Documentation**: Updated architecture and example documentation

### Final Structure:

```
app/Livewire/{Domain}/
├── Components/
│   └── Actions.php              ✅ (standardized naming)
├── Forms/
│   └── CreateEditForm.php       ✅ (descriptive purpose)
├── Modals/
│   └── FormModal.php            ✅ (consistent pattern)
└── Tables/
    ├── Main.php                 ✅ (primary entity table)
    ├── PreviousManagers.php     ✅ (relationship tables)
    ├── PreviousMatches.php      ✅ (descriptive names)
    └── PreviousEvents.php       ✅ (consistent pattern)
```

### Achieved Benefits:
1. **✅ Eliminated redundant suffixes** - folder context provides component type
2. **✅ Descriptive purposes** - `Main.php`, `Actions.php`, `CreateEditForm.php`
3. **✅ Scalable structure** - supports multiple components per domain
4. **✅ Consistent patterns** - same naming rules across all domains
5. **✅ Improved maintainability** - clearer component organization

## Component Naming Conventions

### Class to View Mapping:
- Class: `MatchesTable` → Component: `matches.tables.matches-table`
- Class: `EventMatchesTable` → Component: `matches.tables.event-matches-table`
- **Pattern:** PascalCase class → kebab-case with namespace dots

### Avoid Redundant Domain Prefixes:
- ❌ `WrestlerActionsComponent` (inside `app/Livewire/Wrestlers/Components/`)
- ✅ `ActionsComponent` (directory context makes domain clear)
- ❌ `WrestlerFormModal` → ✅ `FormModal` (when inside Wrestlers directory)
- **Rule:** Domain context from directory structure eliminates need for domain prefix in class names

## Trait Naming Guidelines

### Avoid redundant names:
- ❌ `IsBookableReferee` (redundant if only used by Referee model)
- ✅ `OfficiatesMatches` (descriptive and potentially reusable)

### Use descriptive verbs:
- `OfficiatesMatches` - for entities that officiate matches
- `IsBookableCompetitor` - for entities that compete in matches
- `ManagesEntities` - for entities that manage other entities

## Interface Implementation Strategy

**When to use traits vs direct implementation:**

### Use Traits When:
- Multiple models need the same functionality
- Code would be duplicated across models
- Behavior is cohesive and reusable

### Direct Implementation When:
- Only one model uses the interface
- Implementation is model-specific
- Trait would be overly specific

**Example:** `EventMatchPolicy` is implemented directly since only EventMatch needs it, while `IsBookableCompetitor` is a trait since multiple competitor types use it.