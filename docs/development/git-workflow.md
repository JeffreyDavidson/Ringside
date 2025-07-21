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
4. ✅ **Only then proceed**: Stage and commit changes

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

### Before Creating PR
1. Ensure branch is up to date with development
2. Run all tests locally
3. Review your own changes first
4. Write comprehensive PR description

## Quality Gates

### Pre-Commit Checks
- [ ] On correct branch (not master/development)
- [ ] Code follows style guidelines
- [ ] Tests are passing
- [ ] Documentation is updated

### Pre-Push Checks  
- [ ] Branch is up to date with development
- [ ] All commits have descriptive messages
- [ ] No sensitive data in commits
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
✅ Make small, focused commits  
✅ Write clear commit messages  
✅ Create PRs for all changes  
✅ Keep branches up to date  
✅ Delete merged branches  

### DON'T:
❌ Commit directly to master/development  
❌ Use generic branch names like "fix" or "update"  
❌ Make large, unfocused commits  
❌ Push untested code  
❌ Leave stale branches  
❌ Force push to shared branches  

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