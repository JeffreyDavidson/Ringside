# Coding Standards for ringside

## Laravel Conventions
- Use singular model names (User, Post, not Users, Posts)
- Use plural table names (users, posts)
- Use snake_case for database columns
- Use camelCase for model attributes
- Use PascalCase for class names

## Livewire Best Practices
- Keep components focused and single-purpose
- Use public properties for data binding
- Validate input in the component
- Use lifecycle hooks appropriately
- Emit events for component communication

## Tailwind CSS Guidelines
- Use utility classes over custom CSS
- Follow mobile-first responsive design
- Use consistent spacing scale
- Leverage Tailwind's color palette
- Use component classes for repeated patterns

## File and Directory Management
- **Always clean up empty directories** - When removing files from directories, always check if the directory becomes empty and remove it if it contains no files or only empty subdirectories
- **Maintain clean project structure** - Don't leave orphaned empty directories in the codebase
- **Check before removing** - Use `ls -la` or `find` to verify directory contents before removal
- **Remove recursively when appropriate** - Use `rm -rf` for completely empty directory trees
