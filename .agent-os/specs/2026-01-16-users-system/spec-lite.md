# Users System - Quick Reference

> Parent: @.agent-os/specs/2026-01-16-users-system/spec.md

## Core Concept

Users are account holders who own Promotions. Users do NOT directly own wrestlers or other roster entities - that ownership flows through Promotions.

## Key Relationships

```
User
  └── hasMany Promotion
        └── (Promotion owns all entities)

NOT:
User
  └── hasMany Wrestler (REMOVED)
```

## User Model

| Field | Type | Description |
|-------|------|-------------|
| id | ulid | Primary key |
| name | string | Display name |
| email | string | Unique email |
| email_verified_at | timestamp | Verification timestamp |
| password | string | Hashed password |
| role | Role | Admin or Promoter |
| status | UserStatus | Active, Suspended, etc. |
| remember_token | string | Session token |
| timestamps | - | created_at, updated_at |

## Enums

### Role
```php
enum Role: string
{
    case Admin = 'admin';
    case Promoter = 'promoter';
}
```

### UserStatus
```php
enum UserStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Pending = 'pending';
}
```

## Key Files

| File | Purpose |
|------|---------|
| `app/Models/User.php` | User model |
| `app/Enums/Role.php` | Role enum |
| `app/Enums/UserStatus.php` | Status enum |
| `database/migrations/*_add_role_status_to_users_table.php` | Schema updates |
