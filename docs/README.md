# Ringside Documentation

This directory contains comprehensive project documentation and development tools.

## Product Planning

The [product roadmap](product-roadmap.md) preserves product positioning and
future feature ideas, with focused plans for [promotion management](guides/promotion-management-plan.md)
and the [admin interface](guides/admin-interface-direction.md). These are planning
inputs; current behavior is documented in `/architecture/`, and executable work
is tracked in the Ringside Hermes Kanban board.

## Directory Structure

### `/architecture/`
System architecture documentation, patterns, and design decisions including:
- Core patterns and relationships
- Domain-driven design guidelines  
- Lifecycle operation boundaries
- Livewire component standards
- Business rules and enum usage

### `/development/`
Development guidelines and tools:
- Coding standards and conventions
- Exception writing guides
- PHPStan typing requirements
- Testing methodologies
- Command reference

### `/guidelines/`
Project standards and conventions:
- Conventional commits specification
- Code quality enforcement
- Performance guidelines
- Security best practices
- Database naming conventions

### `/testing/`
Testing documentation and strategies:
- Action testing patterns
- Repository testing guidelines
- Validation testing approaches
- Troubleshooting guides

### `/guides/`
Detailed implementation guides:
- Livewire component development
- Match system implementation
- Business logic patterns

### `/workflows/`
Development process documentation:
- Git workflow requirements
- CI/CD pipeline configuration
- Branch protection enforcement

### `/examples/`
Livewire component examples.

### `/releases/`
Release notes and feature summaries.

## Codex Integration

This Laravel project uses Laravel Boost's Codex integration:

- `AGENTS.md` provides current, package-aware Laravel guidance.
- `.agents/skills/` contains the installed project development skills.
- `.codex/config.toml` registers the project-scoped Laravel Boost MCP server.

### Environment
- Laravel Framework 13 with Livewire 4
- Alpine.js for frontend interactivity
- Tailwind CSS for styling
- Laravel Breeze for authentication

### Getting Started

Open the repository as a trusted Codex project, restart Codex after configuration changes, and use `/mcp` to confirm that `laravel-boost` is enabled.
