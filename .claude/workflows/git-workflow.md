# Git Workflow Requirements

## **CRITICAL: Always Use Feature Branches**
- **NEVER commit directly to development branch**
- **ALL code changes must be on properly named feature branches**
- **ALL changes require PR approval before merging**

## **Branch Protection Enforcement**
**GitHub branch protection rules are enabled for `development` and `master` branches:**

- ✅ **PR Required**: Direct pushes are blocked - all changes must go through pull requests
- ✅ **Status Checks Required**: CI workflow must pass before merge is allowed
- ✅ **Signed Commits Required**: All commits must be cryptographically signed
- ✅ **Auto-Styling Excluded**: Pint workflow cannot make direct commits to protected branches

**What this means in practice:**
```bash
# ❌ This will FAIL - GitHub blocks direct pushes
git push origin development

# ✅ This is the ONLY way to get changes into development/master
git checkout -b feature/my-changes
git push origin feature/my-changes
gh pr create --base development
```

**Protection Benefits:**
- **Prevents accidents** like auto-styling workflows making direct commits
- **Enforces code review** through PR process  
- **Ensures CI passes** before any code reaches protected branches
- **Maintains clean history** with proper commit signing

## Branch Naming Convention
- `feat/feature-name` - New features
- `fix/bug-description` - Bug fixes  
- `tests/fix-description` - Test-related fixes
- `tests/add-description` - Adding new tests
- `refactor/code-improvement` - Code refactoring
- `docs/documentation-update` - Documentation changes
- `chore/task-description` - Maintenance tasks

**Examples:**
- `tests/fix-test-failures` - Fixing failing tests
- `tests/add-policy-coverage` - Adding new test coverage
- `refactor/organize-model-tests` - Restructuring test organization

## Git Commit Message Format

**Always create focused, logical commits separated by concern rather than bundling unrelated changes.**

Use this exact format:

```
type: brief description

- Detailed change 1
- Detailed change 2  
- Additional context or reasoning
```

**Conventional Commit Types for Subject Line:**
- `fix:` - Bug fixes
- `feat:` - New features  
- `docs:` - Documentation updates
- `chore:` - Maintenance tasks
- `refactor:` - Code refactoring
- `test:` - Test additions/improvements
- `style:` - Code formatting changes
- `perf:` - Performance improvements
- `ci:` - CI/CD changes

**Example Commits:**
```bash
git commit -m "fix: correct TagTeams IndexController view name

- Change 'tagteams.index' to 'tag-teams.index' to match actual view file location
- Resolves test failure where view was not found
- Follows kebab-case naming convention for view directories"
```

**Important Notes:**
- Do NOT include "🤖 Generated with [Claude Code](https://claude.ai/code)" 
- Do NOT include "Co-Authored-By: Claude <noreply@anthropic.com>"
- Keep the format clean and professional
- Each commit should focus on a single concern or related group of changes

## TodoList Management Workflow

**CRITICAL: Commit and PR Before Context Switches**

When managing todos:
1. **Complete related tasks together** - Finish all tasks that are logically related
2. **Commit and PR before context switches** - When the next todo is unrelated to current work, commit current changes and create PR
3. **Resource-specific evaluation** - Before proceeding, evaluate if todos pertain to specific resources and should be organized differently
4. **Clean separation** - Don't mix unrelated changes in the same branch/PR

**CRITICAL: Phased Work Requires Separate Branches**

When todos are organized into phases:
1. **Each phase gets its own feature branch** - Never mix phases in the same branch
2. **Complete entire phase before moving to next** - Finish all Phase 1 tasks before starting Phase 2
3. **Create PR per phase** - Each phase should have focused, reviewable changes
4. **Sequential phase execution** - Phase 2 branch should be based on merged Phase 1 changes

**Examples:**
```bash
# Phase 1: Cleanup and consolidation
git checkout -b refactor/stables-actions-phase1-cleanup
# Complete all Phase 1 tasks, commit, create PR, merge

# Phase 2: Integration and orchestration  
git checkout development  # Start from latest
git checkout -b refactor/stables-actions-phase2-integration
# Complete all Phase 2 tasks, commit, create PR, merge

# Phase 3: Standardization
git checkout development  # Start from latest
git checkout -b refactor/stables-actions-phase3-standards
# Complete all Phase 3 tasks, commit, create PR, merge
```

**Benefits:**
- **Focused reviews** - Each PR represents one logical improvement phase
- **Rollback safety** - Can revert individual phases without affecting others
- **Parallel work** - Different team members can work on different phases
- **Clear progression** - Easy to track which phases are complete vs pending

## Pull Request Creation Requirements

**MANDATORY: Always ask for explicit approval before creating PRs**

Before creating any pull request:
1. **Ask the user**: "Is it okay for me to create a PR for these changes?"
2. **Wait for explicit "Yes"** - Do not proceed without clear approval
3. **Only create PR after receiving approval** - Never assume permission

**Example:**
```
The work is complete. Is it okay for me to create a PR for these changes?
```

**Wait for user response before using `gh pr create` command.**

**Example Decision Points:**
- Just finished Rules organization → Next todo is Builders restructure = **COMMIT & PR**
- Just finished Repository work → Next todo is Policy cleanup = **COMMIT & PR** 
- Working on multiple Model tests → Next todo is also Model tests = **CONTINUE**

**Benefits:**
- Cleaner git history with focused PRs
- Easier code review and rollback
- Logical separation of concerns
- Better tracking of architectural changes