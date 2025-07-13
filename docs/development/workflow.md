# Development Workflow

Git workflow, collaboration process, and development best practices for Ringside.

## Claude Code Integration

### Workflow Settings
- **Auto-accept edits**: Always set to **false** by default
- **Plan mode**: Always use **plan mode** for development tasks
- **Review process**: Present plans for approval before implementing changes

### Development Process
1. **Planning Phase**: Understand task and create detailed plan
2. **Approval Phase**: Present plan to user for approval
3. **Implementation Phase**: Make changes with user approval
4. **Review Phase**: User reviews all changes before accepting

### File Creation Policy
- **Permission Required**: Always ask before creating new files or directories
- **Existing Files Preferred**: Edit existing files over creating new ones
- **Exception Handling**: Stop and ask user how to proceed when files are missing

## Git Workflow

### Branch Strategy
- **Main Branch**: `main` (production-ready code)
- **Feature Branches**: `feature/description` or `fix/description`
- **Hotfix Branches**: `hotfix/description` (critical production fixes)

### Commit Guidelines

#### Commit Message Format
```
type(scope): description

🤖 Generated with [Claude Code](https://claude.ai/code)

Co-Authored-By: Claude <noreply@anthropic.com>
```

#### Commit Types
- **feat**: New feature implementation
- **fix**: Bug fix
- **refactor**: Code refactoring without behavior changes
- **test**: Test additions or modifications
- **docs**: Documentation changes
- **style**: Code style/formatting changes
- **chore**: Maintenance tasks

#### Examples
```bash
feat(wrestlers): add employment status tracking

fix(validation): resolve retirement validation edge case

refactor(repositories): consolidate employment management traits

test(models): add comprehensive wrestler model tests
```

### Commit Process

#### Pre-commit Requirements
```bash
# Run all quality checks
composer test

# Verify code formatting
composer test:lint

# Check static analysis
composer test:types
```

#### Commit Command Pattern
```bash
# Stage relevant files
git add file1.php file2.php

# Commit with proper message
git commit -m "$(cat <<'EOF'
feat(wrestlers): add employment status tracking

Add comprehensive employment status management with proper
state transitions and validation.

🤖 Generated with [Claude Code](https://claude.ai/code)

Co-Authored-By: Claude <noreply@anthropic.com>
EOF
)"
```

### Branch Management

#### Creating Feature Branches
```bash
# Create and switch to feature branch
git checkout -b feature/wrestler-employment-tracking

# Push branch with upstream tracking
git push -u origin feature/wrestler-employment-tracking
```

#### Merging Process
```bash
# Update main branch
git checkout main
git pull origin main

# Merge feature branch
git merge feature/wrestler-employment-tracking

# Push merged changes
git push origin main

# Clean up feature branch
git branch -d feature/wrestler-employment-tracking
git push origin --delete feature/wrestler-employment-tracking
```

## Pull Request Process

### Creating Pull Requests
```bash
# Create PR with GitHub CLI
gh pr create --title "Add wrestler employment tracking" --body "$(cat <<'EOF'
## Summary
- Add comprehensive employment status management
- Implement proper state transitions and validation
- Add employment history tracking

## Test plan
- [ ] Run unit tests for employment models
- [ ] Test employment status transitions
- [ ] Verify validation rules work correctly
- [ ] Test UI components for employment management

🤖 Generated with [Claude Code](https://claude.ai/code)
EOF
)"
```

### PR Review Checklist
- [ ] All tests pass (`composer test`)
- [ ] Code follows style guidelines (`composer test:lint`)
- [ ] Static analysis passes (`composer test:types`)
- [ ] Documentation updated if needed
- [ ] Breaking changes documented
- [ ] Migration files included if database changes

## Code Review Process

### Review Guidelines
1. **Functionality**: Does the code solve the problem correctly?
2. **Architecture**: Does it follow established patterns?
3. **Testing**: Are there adequate tests?
4. **Documentation**: Is the code well-documented?
5. **Performance**: Are there any performance concerns?

### Review Checklist
- [ ] Code follows established architecture patterns
- [ ] Proper error handling and validation
- [ ] Unit tests cover new functionality
- [ ] Integration tests for complex workflows
- [ ] Documentation updated appropriately
- [ ] No hardcoded values or magic numbers
- [ ] Proper use of interfaces and contracts

## Development Environment

### Setup Requirements
```bash
# PHP dependencies
composer install

# Node.js dependencies
npm install

# Environment configuration
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate
php artisan db:seed
```

### Development Tools
- **IDE**: PHPStorm, VS Code, or similar with PHP support
- **Database**: MySQL/PostgreSQL for development
- **Testing**: SQLite in-memory for tests
- **Debugging**: Xdebug for step-through debugging

## Code Quality Standards

### Testing Requirements
- **100% Test Coverage**: All new code must have tests
- **Multiple Test Levels**: Unit, Integration, Feature, Browser
- **Fast Test Execution**: Tests should run quickly
- **Reliable Tests**: No flaky or intermittent failures

### Code Standards
- **PSR-12**: PHP coding standards
- **Laravel Conventions**: Follow Laravel best practices
- **Type Safety**: Use strict types and proper type hints
- **Documentation**: Comprehensive PHPDoc comments

## Deployment Workflow

### Production Deployment
```bash
# Build assets
npm run build

# Clear and cache configs
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force

# Restart services
php artisan queue:restart
```

### Rollback Process
```bash
# Rollback migrations if needed
php artisan migrate:rollback

# Revert to previous commit
git revert HEAD

# Clear caches
php artisan optimize:clear
```

## Collaboration Standards

### Communication
- **Clear Commit Messages**: Explain what and why
- **Descriptive PR Titles**: Summarize changes clearly
- **Detailed PR Descriptions**: Include context and testing info
- **Code Comments**: Explain complex business logic

### Documentation
- **Update Documentation**: Keep docs current with code changes
- **Architecture Decisions**: Document significant architectural choices
- **API Changes**: Document any API modifications
- **Migration Notes**: Explain database schema changes

## Troubleshooting

### Common Issues

#### Merge Conflicts
```bash
# View conflicts
git status

# Resolve conflicts manually
# Edit conflicted files

# Stage resolved files
git add conflicted-file.php

# Complete merge
git commit
```

#### Test Failures
```bash
# Run specific test
./vendor/bin/pest tests/Unit/Models/WrestlerTest.php

# Run with coverage
./vendor/bin/pest --coverage

# Debug test failures
./vendor/bin/pest --stop-on-failure
```

#### Environment Issues
```bash
# Clear all caches
php artisan optimize:clear

# Regenerate autoloader
composer dump-autoload

# Check environment
php artisan about
```

## Performance Considerations

### Code Performance
- **Database Queries**: Avoid N+1 problems
- **Caching**: Use appropriate caching strategies
- **Memory Usage**: Monitor memory consumption
- **Response Times**: Keep response times under 200ms

### Development Performance
- **Fast Tests**: Keep test suite execution under 30 seconds
- **Quick Feedback**: Use file watchers for instant feedback
- **Parallel Processing**: Use parallel test execution
- **Incremental Builds**: Optimize build processes

## Security Considerations

### Code Security
- **Input Validation**: Validate all user inputs
- **SQL Injection**: Use parameterized queries
- **XSS Prevention**: Escape output appropriately
- **CSRF Protection**: Use Laravel's CSRF protection

### Development Security
- **Environment Variables**: Never commit secrets
- **Access Control**: Proper authentication and authorization
- **Dependency Updates**: Keep dependencies current
- **Security Scanning**: Regular security audits