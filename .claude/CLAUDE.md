# Claude Code Documentation

## Git Commit Message Format

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

## Relationship Patterns

### Employment Relationships (hired/fired)
- Managers ↔ Wrestlers: `managers_wrestlers` table with `hired_at`/`fired_at`
- Managers ↔ Tag Teams: `managers_tag_teams` table with `hired_at`/`fired_at`
- These represent business employment contracts

### Stable Membership (joined/left)  
- Stables ↔ Wrestlers: `stables_wrestlers` table with `joined_at`/`left_at`
- Stables ↔ Tag Teams: `stables_tag_teams` table with `joined_at`/`left_at`
- These represent stable membership relationships

### Key Architecture Decisions
- NO direct stable-manager relationships
- Separate tables approach over polymorphic for better performance
- Employment status uses `App\Enums\Shared\EmploymentStatus`
- Domain-organized builders in `app/Builders/{Domain}/`

## Enum Organization Standards

### Domain-Organized Enums
- All enums follow domain structure in `app/Enums/{Domain}/`
- Shared enums in `app/Enums/Shared/` for cross-domain usage
- NO root-level enums in `app/Enums/` (removed during cleanup)

### Enum Usage Guidelines
- **Employment Status**: Use `App\Enums\Shared\EmploymentStatus` for pure employment states
- **Activation Status**: Use `App\Enums\Shared\ActivationStatus` for general activation
- **Title Status**: Use `App\Enums\Titles\TitleStatus` for title-specific states
- **User Enums**: Use `App\Enums\Users\Role` and `App\Enums\Users\UserStatus`