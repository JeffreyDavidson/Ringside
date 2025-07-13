# Ringside Documentation

This directory contains comprehensive documentation for the Ringside wrestling promotion management application.

## Quick Navigation

### For Developers
- **[Getting Started](development/commands.md)** - Development commands and setup
- **[Architecture Guide](development/architecture.md)** - Domain structure and patterns
- **[Development Workflow](development/workflow.md)** - Git workflow and collaboration

### For Testing
- **[Testing Standards](testing/standards.md)** - Comprehensive testing guidelines
- **[Validation Rules](testing/validation-rules.md)** - Validation rule testing patterns
- **[Browser Testing](testing/browser-testing.md)** - Dusk and UI testing (future)

### For Code Quality
- **[Code Style Guide](guidelines/code-style.md)** - PSR standards and conventions
- **[Project Conventions](guidelines/conventions.md)** - Naming, structure, and patterns

## About Ringside

Ringside is a Laravel-based web application for wrestling promoters to manage their roster and schedule events. It handles wrestlers, managers, referees, stables, tag teams, titles, events, venues, and matches with complex relationships and time-based tracking.

## Key Features

- **Roster Management**: Complete wrestler, manager, and referee lifecycle
- **Event Scheduling**: Event planning with match creation and competitor booking
- **Title Management**: Championship tracking with reign history
- **Stable & Tag Team Management**: Group management with membership tracking
- **Status Tracking**: Employment, injury, suspension, and retirement states
- **Time-Based Logic**: Activity periods, employment history, championship reigns

## Architecture Highlights

- **Domain-Driven Design**: Modular structure with clear domain boundaries
- **Repository Pattern**: Data access abstraction with trait-based shared functionality
- **Laravel Actions**: Business logic encapsulation using Lorisleiva\Actions
- **Livewire Components**: Interactive UI with standardized base classes
- **Comprehensive Testing**: 100% test coverage with proper separation of concerns

## Documentation Standards

All documentation follows these principles:
- **Modular Organization**: Focused files for specific topics
- **Clear Navigation**: Easy linking between related concepts
- **Practical Examples**: Real code samples and usage patterns
- **Maintenance Focus**: Guidelines for keeping code and docs current

## Getting Help

For questions about:
- **Development Setup**: See [development/commands.md](development/commands.md)
- **Architecture Decisions**: See [development/architecture.md](development/architecture.md)
- **Testing Patterns**: See [testing/standards.md](testing/standards.md)
- **Code Standards**: See [guidelines/code-style.md](guidelines/code-style.md)