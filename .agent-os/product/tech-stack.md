# Tech Stack

## Core Framework & Language

### Backend
- **PHP**: 8.4.11 - Latest stable PHP with modern features
- **Laravel Framework**: 12.25.0 - Latest Laravel with streamlined structure
- **Laravel Tinker**: 2.10.1 - Interactive debugging and exploration

### Frontend
- **Livewire**: 3.6.4 - Dynamic components with server-side state
- **Alpine.js**: Included with Livewire 3 - Client-side reactivity
- **Tailwind CSS**: 4.1.0 - CSS-first configuration with built-in plugins
- **Vite**: 5.0.10 - Modern asset bundling and HMR

## Database & Storage
- **MySQL**: Primary database engine
- **Laravel Migrations**: Schema version control
- **Eloquent ORM**: Advanced relationship management

## Development Quality Tools

### Testing Framework
- **Pest**: 4.0.3 - Modern PHP testing with 100% coverage requirement
- **Pest Browser Plugin**: 4.0.3 - Browser testing (migrated from Laravel Dusk)
- **Missing Livewire Assertions**: 2.11.0 - Enhanced Livewire testing

### Code Quality
- **PHPStan/Larastan**: 3.6.1 - Static analysis with Laravel-specific rules
- **Laravel Pint**: 1.24.0 - Code formatting based on PSR standards
- **Rector**: 2.1.4 - Automated code modernization
- **PHPMND**: 3.4.0 - Magic number detection

### Development Experience
- **Laravel Debugbar**: 3.15.0 - Development debugging toolbar
- **Laravel IDE Helper**: 3.5.0 - IDE support for Laravel features
- **Spatie Laravel Ignition**: 2.9.0 - Beautiful error pages

## Frontend Tooling

### Build & Asset Management
- **Vite Bundle Analyzer**: 1.1.0 - Bundle size analysis
- **PostCSS**: 8.4.33 - CSS processing
- **Autoprefixer**: 10.4.16 - Automatic vendor prefixes

### Code Quality (Frontend)
- **ESLint**: 9.32.0 - JavaScript linting with flat config
- **Prettier**: 3.6.2 - Code formatting for JS and Blade files
- **TypeScript Support**: Parser and plugins for future TypeScript migration
- **Husky**: 9.1.7 - Git hooks for automated quality checks
- **lint-staged**: 16.1.2 - Run linters on staged files only

## Additional Libraries

### Laravel Ecosystem
- **Laravel Boost**: 1.0.0 - MCP server integration
- **Laravel Breeze**: 2.3.0 - Authentication scaffolding
- **Laravel Pail**: 1.2.2 - Log monitoring
- **Laravel Actions**: 2.9.0 - Action-based architecture
- **Laravel Eloquent Relationships**: 2.2.0 - Extended relationship functionality

### Specialized Packages
- **Sushi**: 2.5.0 - Eloquent models from static data
- **Faker**: 1.23.0 - Test data generation
- **Mockery**: 1.6.0 - PHP mocking framework

## Development Commands

### Quality Assurance
```bash
composer test          # Full test suite with all quality checks
composer test:unit      # Pest tests with coverage
composer test:types     # PHPStan static analysis  
composer lint           # Laravel Pint code formatting
composer rector         # Automated code modernization
```

### Development Server
```bash
composer dev            # Start all services (server, queue, logs, vite)
npm run dev             # Vite development server
npm run build           # Production asset build
npm run build:analyze   # Bundle analysis
```

### Frontend Quality
```bash
npm run lint            # ESLint JavaScript checking
npm run lint:fix        # Auto-fix JavaScript issues
npm run format          # Format JS and Blade files
npm run format:check    # Check formatting without changes
```

## Architecture Highlights

### Modern Laravel 12 Structure
- Streamlined directory structure without middleware directory
- `bootstrap/app.php` for middleware and routing configuration
- Automatic command registration from `app/Console/Commands/`

### Advanced Testing Setup
- 100% test coverage requirement enforced
- Comprehensive factory testing with realistic data
- Browser testing with Playwright integration
- Type coverage validation with Pest

### Performance Optimizations
- Tree-shakeable frontend dependencies
- Bundle analysis for performance monitoring
- Efficient CSS-first Tailwind configuration
- Optimized autoloader and dependency management