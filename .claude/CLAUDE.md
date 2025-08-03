# Ringside Project Memory

## Project Overview
[[project-overview.md]]

## Development Guidelines
[[development/tech-stack.md]]
[[development/coding-standards.md]]
[[development/phpstan-typing.md]]
[[development/testing-guidelines.md]]
[[development/commands.md]]
[[development/exception-writing-guide.md]]

## Architecture & Patterns
[[architecture/core-patterns.md]]
[[architecture/livewire-standards.md]]
[[architecture/builders.md]]
[[architecture/business-rules.md]]
[[architecture/enums.md]]
[[architecture/match-generation.md]]

## Development Guidelines & Conventions
[[guidelines/conventions/README.md]]
[[guidelines/testing.md]]
[[guidelines/laravel.md]]
[[guidelines/php.md]]

## Testing & Debugging
[[testing/troubleshooting.md]]
[[testing/action-testing.md]]
[[testing/repository-testing.md]]
[[testing/validation-testing.md]]

## Development Guides
[[guides/livewire/testing-guide.md]]
[[guides/matches/dynamic-ui-system.md]]

## Workflows & Processes
[[workflows/git-workflow.md]]
[[workflows/ci-cd.md]]

## Additional Resources
[[rules.md]]
[[memory_prompts.md]]

## Quick Reference

### Frontend Architecture (Recently Completed)
**Complete modernization from Metronic template to Alpine.js + Livewire stack:**
- **Alpine.js Store**: Global sidebar state management (`Alpine.store('sidebar')`)
- **Component Architecture**: Show pages, table headers, form grids as reusable components
- **Zero Legacy Dependencies**: 100% elimination of Metronic JavaScript and Bootstrap classes
- **Performance**: Reduced from 39 to 24 menu components (38% reduction)
- **Critical Fix**: Sidebar content shifting bug resolved with reactive CSS classes
- **Documentation**: Frontend tools organized in `docs/frontend/` directory

**Key Frontend Patterns:**
- Use `<x-layouts.show-page>` for entity detail pages with sidebar slot
- Use `<x-layouts.table-header>` for index pages with title/actions
- Use `<x-layouts.form-grid>` for responsive form layouts
- Alpine.js store for global state, local `x-data` for component state
- Static utility classes in CSS, avoid dynamic class generation (Vite compatibility)

**Important Conversion Rule:**
- **ANY Metronic template JavaScript/jQuery MUST be converted to Alpine.js + Livewire**
- **NO data-* attributes** from paid templates - convert to Alpine.js patterns
- Maintain 100% visual design accuracy while modernizing interaction code

### Current Focus
Working on TagTeams Actions architectural refinements using service layer patterns with:
- `TagTeamMembershipService` for partnership management
- `TagTeamValidationService` for business rule validation  
- `TagTeamLifecycleService` for employment workflows

### Recent Architectural Decisions
- **Frontend Modernization**: Complete conversion to Alpine.js + Livewire stack
- Service layer pattern implementation for TagTeams Actions
- Component-based architecture with reusable layout components
- Elimination of duplicate code across actions and components
- Enhanced validation and business rule enforcement
- Optimized transaction boundaries with centralized error handling

### Architecture Links
For detailed architecture information, see the documentation in `docs/architecture/`:
- **[Builders](docs/architecture/builders.md)**: Query builder patterns and domain organization
- **[Enums](docs/architecture/enums.md)**: Enum organization standards and usage guidelines
- **[Business Rules](docs/architecture/business-rules.md)**: Wrestling business logic and constraints
- **[Match Generation](docs/architecture/match-generation.md)**: Match creation and validation