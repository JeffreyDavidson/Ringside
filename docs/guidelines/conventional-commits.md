# Conventional Commits Guidelines

This project follows the [Conventional Commits](https://www.conventionalcommits.org/) specification for all commit messages and Pull Request titles.

## Format

```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

## Types

### Primary Types
- **feat**: A new feature
- **fix**: A bug fix
- **docs**: Documentation only changes
- **style**: Changes that do not affect the meaning of the code (white-space, formatting, missing semi-colons, etc)
- **refactor**: A code change that neither fixes a bug nor adds a feature
- **perf**: A code change that improves performance
- **test**: Adding missing tests or correcting existing tests
- **build**: Changes that affect the build system or external dependencies
- **ci**: Changes to our CI configuration files and scripts
- **chore**: Other changes that don't modify src or test files
- **revert**: Reverts a previous commit

### Scope (Optional)
The scope should be the name of the affected area:
- **wrestlers**: Wrestler-related changes
- **managers**: Manager-related changes
- **referees**: Referee-related changes
- **tag-teams**: Tag team-related changes
- **matches**: Match-related changes
- **titles**: Championship-related changes
- **events**: Event-related changes
- **ui**: User interface changes
- **api**: API changes
- **db**: Database changes
- **tests**: Test-related changes

## Examples

### Commit Messages
```bash
feat: add TagTeams Actions architectural refinements

feat(tag-teams): implement comprehensive Integration Test suite

fix(wrestlers): resolve employment status validation bug

docs: update conventional commits guidelines

refactor(managers): remove relationship-specific actions for consistency

test(tag-teams): add comprehensive StatusTransitionPipeline testing

style: fix code formatting issues in Actions components

perf(matches): optimize match creation pipeline performance
```

### Pull Request Titles
```
feat: complete TagTeams Actions architectural refinements with comprehensive enhancements

fix: resolve wrestler employment cascade strategy issues

docs: add comprehensive API documentation for match system

refactor: standardize error handling across all entity Actions

test: implement comprehensive Integration Test coverage for Manager Actions
```

## Rules

### MUST
1. **Use lowercase** for type and scope
2. **Use present tense** ("add" not "added" or "adds")
3. **Use imperative mood** ("fix" not "fixes")
4. **Limit first line to 72 characters**
5. **Include type** (feat, fix, etc.)

### SHOULD
1. **Include scope** when changes affect specific area
2. **Include body** for complex changes
3. **Reference issues** in footer when applicable
4. **Use breaking change footer** for breaking changes

### MUST NOT
1. **Capitalize first letter** of description
2. **End with period** in description
3. **Use past tense** or present continuous

## Breaking Changes

For breaking changes, add `!` after type/scope and include `BREAKING CHANGE:` in footer:

```
feat(api)!: remove deprecated wrestler endpoints

BREAKING CHANGE: The /api/wrestlers/legacy endpoint has been removed. 
Use /api/wrestlers/v2 instead.
```

## Multi-line Messages

For complex changes, use body and footer:

```
feat(tag-teams): implement comprehensive error handling system

Add ErrorMessageMappingService integration with tag team-specific
exception mapping. Includes user-friendly error messages for all
business rule violations and context-aware logging.

- Add mapTagTeamException method
- Enhance tag-teams.php language file  
- Implement 63 specific error messages
- Add context preservation for debugging

Closes #123
```

## Automated Validation

### Pre-commit Hooks
The project uses pre-commit hooks to validate commit message format:

```bash
# Install commitizen for interactive commits
npm install -g commitizen cz-conventional-changelog

# Use interactive commit
git cz
```

### CI/CD Integration
Pull Request titles are automatically validated against conventional commits format. PRs with non-conforming titles will fail CI checks.

## Tools and Resources

### Recommended Tools
- **Commitizen**: Interactive commit message generator
- **Conventional Changelog**: Automatic changelog generation
- **Semantic Release**: Automated versioning and releases

### Installation
```bash
# Global installation
npm install -g commitizen cz-conventional-changelog

# Configure repository
echo '{ "path": "cz-conventional-changelog" }' > ~/.czrc
```

### Usage
```bash
# Interactive commit (recommended)
git cz

# Manual commit (ensure format compliance)
git commit -m "feat(tag-teams): add new partnership management system"
```

## Benefits

### Automation
- **Automated changelog** generation
- **Semantic versioning** based on commit types
- **Release notes** generation
- **CI/CD integration** for automated releases

### Team Communication
- **Clear change intent** through structured format
- **Easy filtering** of commits by type/scope
- **Consistent history** for better code archaeology
- **Integration** with issue tracking systems

## Enforcement

### Required For
- ✅ **All commit messages** in feature branches
- ✅ **All Pull Request titles**
- ✅ **All merge commits** to development/master

### Validation Points
- **Pre-commit hooks** validate message format
- **CI/CD pipeline** validates PR titles
- **Code review** process includes format verification
- **Automated tools** generate changelog and releases

## Quick Reference

### Common Patterns
```bash
# New feature
feat(scope): add new capability

# Bug fix  
fix(scope): resolve specific issue

# Refactoring
refactor(scope): improve code structure

# Tests
test(scope): add comprehensive test coverage

# Documentation
docs: update guidelines and examples

# Breaking change
feat(api)!: remove deprecated endpoints
```

### Scope Examples
```bash
feat(wrestlers): add injury management system
fix(tag-teams): resolve partnership validation
refactor(managers): standardize action patterns
test(matches): add integration test coverage
docs(architecture): update system overview
```

---

**Remember**: Consistent commit messages improve code maintainability, enable automation, and enhance team communication. When in doubt, use the interactive `git cz` command for guided commit message creation.