# Claude Code Documentation

## Git Workflow Requirements

### **CRITICAL: Always Use Feature Branches**
- **NEVER commit directly to development branch**
- **ALL code changes must be on properly named feature branches**
- **ALL changes require PR approval before merging**

### Branch Naming Convention
- `feat/feature-name` - New features
- `fix/bug-description` - Bug fixes
- `chore/task-description` - Maintenance tasks
- `docs/documentation-update` - Documentation changes
- `refactor/code-improvement` - Code refactoring
- `test/testing-improvement` - Test additions/improvements

### Git Commit Message Format

When suggesting git commit commands, use this exact format:

```
git commit -m "type: brief description

- Detailed change 1
- Detailed change 2
- Detailed change 3
- Additional context or reasoning"
```

**Important Notes:**
- Do NOT include "🤖 Generated with [Claude Code](https://claude.ai/code)" 
- Do NOT include "Co-Authored-By: Claude <noreply@anthropic.com>"
- Keep the format clean and professional
- Use conventional commit types (feat, fix, docs, style, refactor, test, chore, etc.)

### TodoList Management Workflow

**CRITICAL: Commit and PR Before Context Switches**

When managing todos:
1. **Complete related tasks together** - Finish all tasks that are logically related
2. **Commit and PR before context switches** - When the next todo is unrelated to current work, commit current changes and create PR
3. **Resource-specific evaluation** - Before proceeding, evaluate if todos pertain to specific resources and should be organized differently
4. **Clean separation** - Don't mix unrelated changes in the same branch/PR

**Example Decision Points:**
- Just finished Rules organization → Next todo is Builders restructure = **COMMIT & PR**
- Just finished Repository work → Next todo is Policy cleanup = **COMMIT & PR** 
- Working on multiple Model tests → Next todo is also Model tests = **CONTINUE**

**Benefits:**
- Cleaner git history with focused PRs
- Easier code review and rollback
- Logical separation of concerns
- Better tracking of architectural changes

## Architecture Documentation

For detailed architecture information, see the documentation in `docs/architecture/`:

- **[Builders](docs/architecture/builders.md)**: Query builder patterns and domain organization
- **[Enums](docs/architecture/enums.md)**: Enum organization standards and usage guidelines
- **[Business Rules](docs/architecture/business-rules.md)**: Wrestling business logic and constraints
- **[Match Generation](docs/architecture/match-generation.md)**: Match creation and validation

## Quick Reference

### Relationship Patterns

#### Employment Relationships (hired/fired)
- Managers ↔ Wrestlers: `managers_wrestlers` table with `hired_at`/`fired_at`
- Managers ↔ Tag Teams: `managers_tag_teams` table with `hired_at`/`fired_at`
- These represent business employment contracts

#### Stable Membership (joined/left)  
- Stables ↔ Wrestlers: `stables_wrestlers` table with `joined_at`/`left_at`
- Stables ↔ Tag Teams: `stables_tag_teams` table with `joined_at`/`left_at`
- These represent stable membership relationships

### Key Architecture Decisions
- NO direct stable-manager relationships
- Separate tables approach over polymorphic for better performance
- Employment status uses `App\Enums\Shared\EmploymentStatus`
- Domain-organized builders in `app/Builders/{Domain}/`
- Domain-organized enums in `app/Enums/{Domain}/`

### Essential Enum Usage
- **Employment Status**: `App\Enums\Shared\EmploymentStatus` for pure employment states
- **Activation Status**: `App\Enums\Shared\ActivationStatus` for general activation
- **Title Status**: `App\Enums\Titles\TitleStatus` for title-specific states
- **User Enums**: `App\Enums\Users\Role` and `App\Enums\Users\UserStatus`