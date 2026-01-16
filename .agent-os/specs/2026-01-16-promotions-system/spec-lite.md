# Promotions System - Quick Reference

> Parent: @.agent-os/specs/2026-01-16-promotions-system/spec.md

## Core Concept

Promotions are the multi-tenant ownership layer. Every roster entity, event, venue, and title belongs to exactly one Promotion.

## Key Relationships

```
User (Promoter)
  └── hasMany Promotion
        ├── hasMany Wrestler
        ├── hasMany TagTeam
        ├── hasMany Manager
        ├── hasMany Referee
        ├── hasMany Stable
        ├── hasMany Event
        ├── hasMany Venue
        └── hasMany Title
```

## Promotion Model

| Field | Type | Description |
|-------|------|-------------|
| id | ulid | Primary key |
| user_id | foreignId | Owner/Promoter |
| name | string | Promotion name |
| slug | string | URL-safe identifier |
| settings | json | Promotion preferences |
| timestamps | - | created_at, updated_at |

## Entity Scoping

All entities gain `promotion_id` foreign key:
- Wrestlers, Tag Teams, Managers, Referees, Stables
- Events, Venues
- Titles

## Global Scope Pattern

```php
// Applied to all promotion-owned models
class PromotionScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('promotion_id', current_promotion_id());
    }
}
```

## Key Files

| File | Purpose |
|------|---------|
| `app/Models/Promotion.php` | Promotion model |
| `app/Models/Scopes/PromotionScope.php` | Global scope |
| `database/migrations/*_create_promotions_table.php` | Migration |
| `database/migrations/*_add_promotion_id_to_*_tables.php` | FK migrations |
