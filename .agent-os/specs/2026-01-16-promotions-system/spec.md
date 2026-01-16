# Spec Requirements Document

> Spec: Promotions System
> Created: 2026-01-16
> Status: Planning

## Overview

Create a multi-tenant Promotions system that serves as the ownership layer for all wrestling entities. Each Promotion represents a wrestling company owned by a User (Promoter), and owns all roster members, events, venues, and titles within its scope.

## User Stories

### Promoter Story

As a promoter, I want to create and manage my wrestling promotion, so that I can organize my roster, schedule events, and track championships within my company's scope.

**Detailed Workflow**: Promoters register an account, create their promotion with basic details (name, slug, settings), and then manage all roster entities (wrestlers, tag teams, managers, referees, stables), schedule events at venues, and establish championship titles - all scoped to their promotion.

### Multi-Promotion Story

As a promoter with multiple brands, I want to manage separate promotions under my account, so that I can run distinct wrestling companies with their own rosters and championships.

**Detailed Workflow**: A single user account can own multiple promotions, each with completely separate rosters, events, and titles. Switching between promotions provides isolated management contexts.

### Data Isolation Story

As a promoter, I want my promotion's data to be completely isolated from other promotions, so that my roster, events, and business operations remain private and secure.

**Detailed Workflow**: All queries are automatically scoped to the current promotion context. A promotion's wrestlers, events, titles, and other entities are never visible to or accessible by other promotions.

## Spec Scope

1. **Promotion Entity** - Core promotion model with name, slug, settings, and owner relationship
2. **Ownership Model** - User (Promoter) ownership of one or more Promotions
3. **Entity Scoping** - All roster and event entities belong to a Promotion via `promotion_id`
4. **Promotion Context** - Session/request-level promotion scoping for multi-promotion users
5. **Promotion Settings** - Configurable options per promotion (timezone, currency, display preferences)

## Out of Scope

- Cross-promotion talent sharing/loans (future feature)
- Cross-promotion events (future feature)
- Promotion federation/alliance systems
- Public promotion directories or discovery
- Promotion subscription/billing tiers

## Expected Deliverable

1. Promotion model with proper relationships to User (owner) and all owned entities
2. Migration adding `promotion_id` foreign key to all entity tables
3. Global scope or middleware ensuring promotion-level data isolation
4. Promotion switching mechanism for multi-promotion users
5. 100% test coverage for promotion ownership and scoping logic

## Spec Documentation

- Tasks: @.agent-os/specs/2026-01-16-promotions-system/tasks.md
- Technical Specification: @.agent-os/specs/2026-01-16-promotions-system/sub-specs/technical-spec.md
