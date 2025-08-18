# Ringside Documentation

This directory contains comprehensive project documentation and development tools.

## Directory Structure

### `/architecture/`
System architecture documentation, patterns, and design decisions including:
- Core patterns and relationships
- Domain-driven design guidelines  
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

### `/frontend/`
Frontend development documentation and tools, including:
- **Metronic Integration Documentation**: Component usage, maintenance checklists, and quality assurance tools
- **Visual Comparison Tools**: HTML-based checklists for design accuracy validation  
- **Color Validation Scripts**: JavaScript tools for ensuring Metronic color scheme compliance

These tools were created during the frontend modernization process to maintain design accuracy while converting from Metronic template code to custom Alpine.js + Tailwind CSS implementation.

## Claude Code Integration

This Laravel project is configured with Claude Code and MCP servers for enhanced development:

### Available MCP Servers
**Global Servers** (shared across all projects):
- **GitHub** - Repository access and management
- **Memory** - Shared knowledge base across projects
- **Context7** - Latest documentation access
- **Web Fetch** - External API and resource access

**Project-Specific Servers**:
- **Filesystem** - Access to this project's files
- **Database** - Direct database access for this project
- **Laravel DebugBar** (if installed) - Debug information

### Environment
- Laravel Framework with Livewire 3
- Alpine.js for frontend interactivity
- Tailwind CSS for styling
- Laravel Breeze for authentication

### Getting Started
Run `source .claude/shortcuts.sh` to load helpful development aliases.
EOF < /dev/null