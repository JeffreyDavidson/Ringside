# Spec Requirements Document

> Spec: Users System
> Created: 2026-01-16
> Status: Planning

## Overview

Define the Users system that handles authentication, user profiles, and promotion ownership. Users serve as the account layer - they own Promotions, which in turn own all roster entities. Users do NOT directly own wrestlers or other roster members.

## User Stories

### Account Registration Story

As a prospective promoter, I want to create an account with my email and password, so that I can start managing my wrestling promotion.

**Detailed Workflow**: Users register with email, password, and basic profile information. Upon registration, they can create their first Promotion to begin managing roster and events.

### Profile Management Story

As a registered user, I want to manage my account profile and settings, so that I can keep my information current and customize my experience.

**Detailed Workflow**: Users can update their name, email, password, and account preferences. Profile changes are separate from promotion-level settings.

### Promotion Ownership Story

As a user, I want to own one or more wrestling promotions, so that I can manage separate brands or companies under a single account.

**Detailed Workflow**: Users create and own Promotions. All roster management (wrestlers, tag teams, etc.) happens at the Promotion level, not the User level.

## Spec Scope

1. **User Entity** - Core user model for authentication and profile
2. **Authentication** - Login, logout, password reset using Laravel Breeze/Fortify
3. **User Roles** - Admin and Promoter role distinction
4. **User Status** - Account status (Active, Suspended, etc.)
5. **Promotion Relationship** - User owns Promotions (no direct entity ownership)

## Out of Scope

- Social authentication (OAuth providers)
- Two-factor authentication
- Team/multi-user promotion management
- User permissions beyond basic roles
- User activity logging/audit trail

## Expected Deliverable

1. User model with proper relationships to Promotions only (no direct wrestler relationship)
2. Role and UserStatus enums for type-safe status management
3. Authentication scaffolding using Laravel's built-in tools
4. 100% test coverage for user authentication and relationships

## Spec Documentation

- Tasks: @.agent-os/specs/2026-01-16-users-system/tasks.md
- Technical Specification: @.agent-os/specs/2026-01-16-users-system/sub-specs/technical-spec.md
