# Ringside Documentation

Comprehensive documentation for the Ringside wrestling promotion management system.

## Overview

Ringside is a Laravel-based application for managing wrestling promotions, including wrestlers, matches, championships, and events. This documentation provides comprehensive guidance for development, architecture, and maintenance.

## Quick Start

### Development Setup
```bash
# Clone the repository
git clone <repository-url>
cd ringside

# Install dependencies
composer install
npm install

# Set up environment
cp .env.example .env
php artisan key:generate

# Run migrations and seeders
php artisan migrate --seed

# Start development server
php artisan serve
```

### Code Quality Tools
```bash
# Code formatting
composer lint

# Static analysis
composer test:types

# Test coverage
composer test:coverage

# Code modernization
composer rector
```

## Documentation Structure

### Development Guidelines
- **[Code Style Guide](guidelines/code-style.md)** - Comprehensive coding standards
- **[PHP Standards](guidelines/php.md)** - PHP-specific conventions
- **[Laravel Conventions](guidelines/laravel.md)** - Laravel framework patterns
- **[Testing Standards](guidelines/testing.md)** - Testing conventions and practices
- **[Quality Tools](guidelines/quality-tools.md)** - Automated quality assurance

### Architecture Documentation
- **[Business Rules](architecture/business-rules.md)** - Core business logic and rules
- **[Core Capabilities](architecture/core-capabilities.md)** - Entity capabilities and restrictions
- **[Match System](architecture/match-system.md)** - Match types and competitor rules
- **[Championship System](architecture/championship-system.md)** - Title management and validation

### Naming Conventions
- **[Class Naming](guidelines/conventions/class-naming.md)** - Model, action, and controller naming
- **[File Naming](guidelines/conventions/file-naming.md)** - Directory structure and file organization
- **[Database Naming](guidelines/conventions/database-naming.md)** - Table and column naming standards
- **[Method Naming](guidelines/conventions/method-naming.md)** - Method naming patterns
- **[Variable Naming](guidelines/conventions/variable-naming.md)** - Variable naming conventions

## Key Concepts

### Domain Entities
- **Wrestlers** - Individual performers with employment, injury, and retirement status
- **Tag Teams** - Groups of wrestlers who compete together
- **Stables** - Groups of wrestlers and tag teams with shared affiliations
- **Titles** - Championships that can be won, lost, and defended
- **Events** - Wrestling shows with scheduled matches
- **Venues** - Locations where events are held

### Business Capabilities
- **Employment** - Managing working relationships with the promotion
- **Injury Management** - Tracking wrestler injuries and recovery
- **Suspension System** - Managing disciplinary actions
- **Retirement Tracking** - Handling career endings
- **Match Booking** - Scheduling and managing wrestling matches
- **Championship Management** - Title defenses and changes

## Development Workflow

### Code Standards
1. **Follow PSR-12** - PHP coding standards compliance
2. **Use Strict Types** - `declare(strict_types=1);` in all files
3. **Document Everything** - Comprehensive PHPDoc for all classes and methods
4. **Write Tests** - 100% test coverage requirement
5. **Type Safety** - PHPStan level 6 static analysis

### Quality Assurance
- **Automated Formatting** - Laravel Pint for code formatting
- **Static Analysis** - PHPStan for type checking
- **Test Coverage** - Pest for comprehensive testing
- **Code Modernization** - Rector for automated updates

## Contributing

### Development Process
1. **Create Feature Branch** - Branch from main for new features
2. **Follow Standards** - Adhere to all coding and documentation standards
3. **Write Tests** - Include comprehensive test coverage
4. **Update Documentation** - Keep documentation current with changes
5. **Submit Pull Request** - Include detailed description and testing notes

### Code Review Process
- **Automated Checks** - CI/CD pipeline runs quality checks
- **Manual Review** - Code review by team members
- **Quality Gates** - Must pass all quality checks for merge
- **Documentation Review** - Ensure documentation is updated

## Support and Resources

### Getting Help
- **Documentation** - Comprehensive guides in this documentation
- **Code Examples** - See test files for usage examples
- **Architecture Guides** - Detailed architecture documentation
- **Development Standards** - Clear coding and testing standards

### Additional Resources
- **Laravel Documentation** - [laravel.com/docs](https://laravel.com/docs)
- **PHP Documentation** - [php.net/manual](https://php.net/manual)
- **Pest Testing** - [pestphp.com](https://pestphp.com)
- **PHPStan** - [phpstan.org](https://phpstan.org)

## License

This project is proprietary software. All rights reserved.
