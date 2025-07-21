# Development Workflow Index

This development workflow guide has been split into focused, semantically named documents for easier navigation, renderer compatibility, and maintainability. Each section below links to a dedicated file covering a specific area of development workflow in Ringside.

## ⚠️ CRITICAL SAFETY REMINDER

**ALWAYS verify you are NOT on `master` or `development` branch before committing!**

```bash
# Check current branch before any commit
git branch --show-current

# If on protected branch, create feature branch immediately
git checkout -b feature/your-feature-name
```

See [Git Workflow](git-workflow.md) for complete branch safety guidelines.

## Sections
- **[Git Workflow](git-workflow.md)** - **🔥 START HERE** - Branch safety and git best practices
- [Claude Integration](claude-integration.md)
- [Pull Request Process](pull-request-process.md)
- [Code Review](code-review.md)
- [Development Environment](development-environment.md)
- [Code Quality](code-quality.md)
- [Collaboration Process](collaboration-process.md)

Refer to each file for detailed workflow guidelines and best practices.
