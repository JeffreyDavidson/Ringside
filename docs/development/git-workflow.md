# Git Workflow Guidelines

## Branch Safety - CRITICAL

### ⚠️ **ALWAYS VERIFY BRANCH BEFORE COMMITTING**

**NEVER commit directly to `master` or `development` branches!**

Before creating any commit, always verify you are on the correct branch:

```bash
# Always check current branch first
git branch --show-current

# Or check with git status
git status

# If on master/development, CREATE A NEW BRANCH
git checkout -b feature/your-feature-name
```

### Pre-Commit Safety Checklist

1. ✅ **Check current branch**: `git branch --show-current`
2. ✅ **Verify you are NOT on**: `master`, `main`, or `development`
3. ✅ **If on protected branch**: Create new branch immediately
4. ✅ **Run targeted tests**: Use Pest groups to test relevant changes
5. ✅ **Verify test groups**: Ensure new/modified tests have proper groups
6. ✅ **Only then proceed**: Stage and commit changes

### Pre-Commit Testing

Before committing, run tests for areas you've modified:

```bash
# If working on managers domain
vendor/bin/pest --group=managers

# If working on table components  
vendor/bin/pest --group=tables

# If working on employment features
vendor/bin/pest --group=employment

# For new tests, verify groups work
vendor/bin/pest --group=managers,integration
```

### Branch Creation Pattern

```bash
# Check current branch
git branch --show-current

# If on protected branch, create feature branch
git checkout -b fix/descriptive-issue-name
# or
git checkout -b feature/descriptive-feature-name
# or  
git checkout -b docs/documentation-update-name

# Now safe to commit
git add .
git commit -m "your commit message"
```

## Branch Naming Conventions

### Standard Prefixes
- `feature/` - New functionality
- `fix/` - Bug fixes
- `docs/` - Documentation updates
- `refactor/` - Code refactoring
- `test/` - Test improvements
- `chore/` - Maintenance tasks

### Examples
```bash
git checkout -b feature/dynamic-match-ui
git checkout -b fix/referees-formmodal-clear-functionality  
git checkout -b docs/match-ui-enhancement-documentation
git checkout -b refactor/livewire-component-structure
```

## Protected Branches

### Never Commit Directly To:
- `master` / `main` - Production branch
- `development` - Main development integration branch

### Why This Matters:
- **Code Review**: All changes should go through PR review
- **CI/CD**: Direct commits bypass automated testing
- **History**: Maintains clean commit history  
- **Collaboration**: Prevents conflicts with team members
- **Quality**: Ensures all changes meet quality standards

## Safe Commit Workflow

### 1. Pre-Work Setup
```bash
# Start from development
git checkout development
git pull origin development

# Create feature branch
git checkout -b feature/my-new-feature
```

### 2. During Development
```bash
# Always verify branch before committing
git branch --show-current  # Should show your feature branch

# Stage and commit
git add .
git commit -m "descriptive commit message"
```

### 3. Push and PR
```bash
# Push to origin
git push -u origin feature/my-new-feature

# Create PR via GitHub CLI or web interface
gh pr create --title "Feature: My New Feature" --body "Description..."
```

## Recovery From Mistakes

### If You Accidentally Committed to Protected Branch:

#### Option 1: Move Commits to New Branch
```bash
# Create new branch from current position
git checkout -b fix/accidental-commits

# Reset protected branch to previous state
git checkout development
git reset --hard HEAD~1  # Remove last commit

# Push the new branch
git checkout fix/accidental-commits
git push -u origin fix/accidental-commits
```

#### Option 2: Soft Reset (if not pushed)
```bash
# Soft reset to unstage commits
git reset --soft HEAD~1

# Create proper branch
git checkout -b fix/proper-branch-name

# Recommit
git add .
git commit -m "proper commit message"
```

## Branch Management

### Regular Cleanup
```bash
# List all branches
git branch -a

# Delete merged feature branches
git branch -d feature/completed-feature

# Delete remote tracking branches
git remote prune origin
```

### Keep Feature Branches Updated
```bash
# Switch to development
git checkout development
git pull origin development

# Switch back to feature branch
git checkout feature/my-feature

# Rebase onto latest development
git rebase development
```

## Team Collaboration

### Before Starting Work
1. Check for existing branches: `git branch -a`
2. Pull latest changes: `git pull origin development`
3. Create descriptive branch name
4. Communicate with team about your work

### During Development
1. Make frequent, small commits
2. Keep commits focused on single changes
3. Write descriptive commit messages
4. Push regularly to backup work
5. Only include necessary files for each specific commit
6. Make as many commits as needed - don't bundle unrelated changes

### Before Creating PR
1. Ensure branch is up to date with development
2. Run all tests locally
3. Review your own changes first
4. Write comprehensive PR description

## Commit Best Practices

### Focus Each Commit on a Single Purpose

**✅ GOOD - Focused commits:**
```bash
# Separate commits for different concerns
git add tests/Unit/Models/TitleTest.php
git commit -m "test: add Title model validation tests"

git add app/Models/Title.php
git commit -m "feat: add status validation to Title model"

git add database/migrations/add_status_to_titles.php
git commit -m "feat: add status column to titles table"
```

**❌ BAD - Mixed concerns in one commit:**
```bash
# Don't bundle unrelated changes
git add tests/Unit/Models/TitleTest.php app/Models/Title.php app/Models/Wrestler.php
git commit -m "fix various issues"
```

### Include Only Necessary Files

**Each commit should contain ONLY the files needed for that specific change:**

```bash
# Example: Fixing a specific bug
git add app/Actions/Titles/DebutAction.php
git add tests/Unit/Actions/Titles/DebutActionTest.php
git commit -m "fix: resolve title debut validation error"

# Example: Adding new feature  
git add app/Livewire/Components/MatchForm.php
git add resources/views/livewire/match-form.blade.php
git add tests/Feature/Livewire/MatchFormTest.php
git commit -m "feat: add dynamic match type selection UI"
```

### Make Multiple Commits Per Branch

**Don't bundle everything into one commit. Break work into logical steps:**

```bash
# Good workflow - multiple focused commits on a branch
git checkout -b feature/user-notifications

# Step 1: Database changes
git add database/migrations/create_notifications_table.php
git commit -m "feat: create notifications table migration"

# Step 2: Model and logic
git add app/Models/Notification.php
git add app/Services/NotificationService.php  
git commit -m "feat: add Notification model and service logic"

# Step 3: Tests
git add tests/Unit/Models/NotificationTest.php
git add tests/Unit/Services/NotificationServiceTest.php
git commit -m "test: add comprehensive notification tests"

# Step 4: UI components
git add app/Livewire/NotificationCenter.php
git add resources/views/livewire/notification-center.blade.php
git commit -m "feat: add notification center UI component"

# Step 5: Integration
git add app/Http/Controllers/NotificationController.php
git add routes/web.php
git commit -m "feat: add notification routes and controller"
```

### Commit Message Guidelines

Follow conventional commit format:

```bash
# Types
feat:     # New feature
fix:      # Bug fix  
docs:     # Documentation changes
test:     # Adding or updating tests
refactor: # Code refactoring
style:    # Code style changes (formatting, etc.)
chore:    # Maintenance tasks

# Examples
git commit -m "feat: add user role-based permissions"
git commit -m "fix: resolve null pointer exception in UserService"
git commit -m "test: add integration tests for auth workflow"
git commit -m "docs: update API documentation for user endpoints"
git commit -m "refactor: extract common validation logic"
```

### When to Split vs Combine Files

**Split into separate commits when:**
- Changes serve different purposes
- Files are in different domains/modules
- One change is a prerequisite for another
- Changes have different risk levels

**Combine in same commit when:**
- Files are tightly coupled for the change
- All changes are required for feature to work
- Changes are purely syntactic (e.g., renaming across files)

**Example - Split approach:**
```bash
# Database schema change
git add database/migrations/add_status_to_titles.php
git commit -m "feat: add status column to titles table"

# Model updates to use new column
git add app/Models/Title.php
git commit -m "feat: integrate status column in Title model"

# Update tests for new functionality
git add tests/Unit/Models/TitleTest.php
git commit -m "test: add tests for Title status functionality"

# UI changes to display status
git add resources/views/titles/show.blade.php
git add app/Livewire/TitleDetails.php
git commit -m "feat: display title status in UI"
```

### Review Your Commits Before Pushing

```bash
# Review commit history
git log --oneline -10

# Review specific commit
git show <commit-hash>

# Interactive rebase to clean up if needed (before pushing)
git rebase -i HEAD~3
```

## Quality Gates

### Pre-Commit Checks
- [ ] On correct branch (not master/development)
- [ ] Only necessary files included for this specific change
- [ ] Commit serves single, clear purpose
- [ ] Code follows style guidelines
- [ ] Tests are passing
- [ ] Documentation is updated

### Pre-Push Checks  
- [ ] Branch is up to date with development
- [ ] All commits have descriptive messages
- [ ] Each commit is focused and purposeful
- [ ] No sensitive data in commits
- [ ] Commit history tells a clear story
- [ ] Ready for team review

## Emergency Procedures

### Hotfix to Production
```bash
# Create hotfix branch from master
git checkout master
git pull origin master
git checkout -b hotfix/critical-bug-fix

# Make minimal changes
# Test thoroughly
# Create PR targeting master

# After merge, also merge to development
```

### Reverting Changes
```bash
# Revert specific commit
git revert <commit-hash>

# Revert merge commit
git revert -m 1 <merge-commit-hash>
```

## Best Practices Summary

### DO:
✅ Always check current branch before committing  
✅ Use descriptive branch names  
✅ Make small, focused commits with single purpose
✅ Include only necessary files for each specific commit
✅ Make multiple commits per branch - don't bundle unrelated changes
✅ Write clear commit messages using conventional format
✅ Create PRs for all changes  
✅ Keep branches up to date  
✅ Delete merged branches
✅ Review commit history before pushing

### DON'T:
❌ Commit directly to master/development  
❌ Use generic branch names like "fix" or "update"  
❌ Make large, unfocused commits with mixed concerns
❌ Bundle unrelated files in the same commit
❌ Use vague commit messages like "fix stuff" or "updates"
❌ Push untested code  
❌ Leave stale branches  
❌ Force push to shared branches
❌ Include files that aren't necessary for the specific change  

## Tools and Aliases

### Useful Git Aliases
```bash
# Add to ~/.gitconfig
[alias]
    current = branch --show-current
    safe = "!f() { if [[ $(git current) == 'master' || $(git current) == 'development' ]]; then echo 'ERROR: On protected branch!'; exit 1; fi; }; f"
    commit-safe = "!git safe && git commit"
```

### Usage
```bash
# Check current branch
git current

# Safe commit (will error if on protected branch)
git commit-safe -m "message"
```

---

**Remember: When in doubt, check your branch! It takes 2 seconds and prevents hours of cleanup.**