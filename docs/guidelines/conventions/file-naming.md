# File Naming Conventions

Directory structure and file naming patterns for Ringside development.

## Overview

Consistent file naming ensures clear project organization and easy navigation.

## Directory Structure

### Organization Standards
- **Domain Organization**: Group files by domain/entity
- **Consistent Nesting**: Use consistent directory nesting
- **Pluralization**: Use plural for directories, singular for files

```
app/
├── Models/
│   ├── Wrestlers/
│   │   ├── Wrestler.php
│   │   ├── WrestlerEmployment.php
│   │   └── WrestlerRetirement.php
│   └── TagTeams/
│       ├── TagTeam.php
│       └── TagTeamWrestler.php
├── Actions/
│   ├── Wrestlers/
│   │   ├── EmployAction.php
│   │   └── RetireAction.php
│   └── TagTeams/
│       └── CreateAction.php
```

## Test File Naming

### Test Organization Standards
- **Mirror App Structure**: Test files mirror app directory structure
- **Descriptive Suffixes**: Use descriptive suffixes for test types
- **Consistent Naming**: Follow established naming patterns

```
tests/
├── Unit/
│   ├── Models/
│   │   ├── Wrestlers/
│   │   │   └── WrestlerTest.php
│   │   └── TagTeams/
│   │       └── TagTeamTest.php
│   └── Rules/
│       └── Wrestlers/
│           └── IsBookableUnitTest.php
├── Integration/
│   ├── Actions/
│   │   └── Wrestlers/
│   │       └── EmployActionTest.php
│   └── Rules/
│       └── Wrestlers/
│           └── IsBookableIntegrationTest.php
```

## Related Documentation
- [Naming Conventions](naming.md)
- [Structural Patterns](structure.md)
- [Testing Conventions](testing.md)
