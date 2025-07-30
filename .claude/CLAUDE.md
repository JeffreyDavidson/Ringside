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

### Current Focus
Working on TagTeams Actions architectural refinements using service layer patterns with:
- `TagTeamMembershipService` for partnership management
- `TagTeamValidationService` for business rule validation  
- `TagTeamLifecycleService` for employment workflows

### Recent Architectural Decisions
- Service layer pattern implementation for TagTeams Actions
- Elimination of duplicate code across actions
- Enhanced validation and business rule enforcement
- Optimized transaction boundaries with centralized error handling

### Architecture Links
For detailed architecture information, see the documentation in `docs/architecture/`:
- **[Builders](docs/architecture/builders.md)**: Query builder patterns and domain organization
- **[Enums](docs/architecture/enums.md)**: Enum organization standards and usage guidelines
- **[Business Rules](docs/architecture/business-rules.md)**: Wrestling business logic and constraints
- **[Match Generation](docs/architecture/match-generation.md)**: Match creation and validation