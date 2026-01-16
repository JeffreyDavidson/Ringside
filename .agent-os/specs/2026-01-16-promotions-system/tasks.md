# Implementation Tasks: Promotions System

> Reference: @.agent-os/specs/2026-01-16-promotions-system/spec.md

---

## Phase 1: Core Infrastructure

### Task 1.1: Create Promotions Migration
**Priority:** Critical
**Estimate:** Small

Create the promotions table migration.

**Files:**
- `database/migrations/YYYY_MM_DD_create_promotions_table.php`

**Acceptance Criteria:**
- [ ] Migration creates promotions table with all fields
- [ ] Proper indexes on user_id and slug
- [ ] Slug has unique constraint
- [ ] Cascade delete on user deletion

---

### Task 1.2: Create Promotion Model
**Priority:** Critical
**Estimate:** Small

Create the Promotion model with relationships.

**Files:**
- `app/Models/Promotion.php`

**Acceptance Criteria:**
- [ ] Model uses HasUlids trait
- [ ] All fillable fields defined
- [ ] Settings cast to array
- [ ] Owner relationship (belongsTo User)
- [ ] All entity relationships defined (hasMany)

---

### Task 1.3: Create BelongsToPromotion Trait
**Priority:** Critical
**Estimate:** Small

Create trait for promotion-owned entities.

**Files:**
- `app/Models/Concerns/BelongsToPromotion.php`

**Acceptance Criteria:**
- [ ] Boots global scope automatically
- [ ] Auto-assigns promotion_id on creating
- [ ] Defines promotion() relationship

---

### Task 1.4: Create PromotionScope
**Priority:** Critical
**Estimate:** Small

Create global scope for promotion filtering.

**Files:**
- `app/Models/Scopes/PromotionScope.php`

**Acceptance Criteria:**
- [ ] Filters by current promotion context
- [ ] Handles null context gracefully
- [ ] Uses qualified column name (table.promotion_id)

---

### Task 1.5: Create Helper Functions
**Priority:** Critical
**Estimate:** Small

Create promotion context helper functions.

**Files:**
- `app/helpers.php`
- `composer.json` (autoload)

**Acceptance Criteria:**
- [ ] current_promotion_id() returns session value
- [ ] current_promotion() returns Promotion model
- [ ] set_current_promotion() updates session
- [ ] Helpers autoloaded via Composer

---

## Phase 2: Entity Migrations

### Task 2.1: Add promotion_id to Wrestlers
**Priority:** Critical
**Estimate:** Small

Add promotion foreign key to wrestlers table.

**Files:**
- `database/migrations/YYYY_MM_DD_add_promotion_id_to_wrestlers_table.php`

**Acceptance Criteria:**
- [ ] Adds promotion_id column
- [ ] Foreign key constraint to promotions
- [ ] Index on promotion_id
- [ ] Cascade delete

---

### Task 2.2: Add promotion_id to Tag Teams
**Priority:** Critical
**Estimate:** Small

Add promotion foreign key to tag_teams table.

**Files:**
- `database/migrations/YYYY_MM_DD_add_promotion_id_to_tag_teams_table.php`

**Acceptance Criteria:**
- [ ] Adds promotion_id column
- [ ] Foreign key constraint
- [ ] Index on promotion_id

---

### Task 2.3: Add promotion_id to Managers
**Priority:** Critical
**Estimate:** Small

Add promotion foreign key to managers table.

**Files:**
- `database/migrations/YYYY_MM_DD_add_promotion_id_to_managers_table.php`

**Acceptance Criteria:**
- [ ] Adds promotion_id column
- [ ] Foreign key constraint
- [ ] Index on promotion_id

---

### Task 2.4: Add promotion_id to Referees
**Priority:** Critical
**Estimate:** Small

Add promotion foreign key to referees table.

**Files:**
- `database/migrations/YYYY_MM_DD_add_promotion_id_to_referees_table.php`

**Acceptance Criteria:**
- [ ] Adds promotion_id column
- [ ] Foreign key constraint
- [ ] Index on promotion_id

---

### Task 2.5: Add promotion_id to Stables
**Priority:** Critical
**Estimate:** Small

Add promotion foreign key to stables table.

**Files:**
- `database/migrations/YYYY_MM_DD_add_promotion_id_to_stables_table.php`

**Acceptance Criteria:**
- [ ] Adds promotion_id column
- [ ] Foreign key constraint
- [ ] Index on promotion_id

---

### Task 2.6: Add promotion_id to Events
**Priority:** Critical
**Estimate:** Small

Add promotion foreign key to events table.

**Files:**
- `database/migrations/YYYY_MM_DD_add_promotion_id_to_events_table.php`

**Acceptance Criteria:**
- [ ] Adds promotion_id column
- [ ] Foreign key constraint
- [ ] Index on promotion_id

---

### Task 2.7: Add promotion_id to Venues
**Priority:** Critical
**Estimate:** Small

Add promotion foreign key to venues table.

**Files:**
- `database/migrations/YYYY_MM_DD_add_promotion_id_to_venues_table.php`

**Acceptance Criteria:**
- [ ] Adds promotion_id column
- [ ] Foreign key constraint
- [ ] Index on promotion_id

**Note:** Consider if venues should be shared across promotions (future enhancement).

---

### Task 2.8: Add promotion_id to Titles
**Priority:** Critical
**Estimate:** Small

Add promotion foreign key to titles table.

**Files:**
- `database/migrations/YYYY_MM_DD_add_promotion_id_to_titles_table.php`

**Acceptance Criteria:**
- [ ] Adds promotion_id column
- [ ] Foreign key constraint
- [ ] Index on promotion_id

---

## Phase 3: Model Updates

### Task 3.1: Add BelongsToPromotion to Entity Models
**Priority:** Critical
**Estimate:** Medium

Update all entity models to use BelongsToPromotion trait.

**Files:**
- `app/Models/Wrestler.php`
- `app/Models/TagTeam.php`
- `app/Models/Manager.php`
- `app/Models/Referee.php`
- `app/Models/Stable.php`
- `app/Models/Event.php`
- `app/Models/Venue.php`
- `app/Models/Title.php`

**Acceptance Criteria:**
- [ ] Each model uses BelongsToPromotion trait
- [ ] promotion_id added to fillable
- [ ] Existing functionality unaffected

---

### Task 3.2: Update User Model
**Priority:** Critical
**Estimate:** Small

Update User model relationships for promotion ownership.

**Files:**
- `app/Models/User.php`

**Acceptance Criteria:**
- [ ] Remove wrestlers() relationship
- [ ] Add promotions() relationship
- [ ] Add currentPromotion() accessor

---

## Phase 4: Middleware & Context

### Task 4.1: Create EnsurePromotionContext Middleware
**Priority:** High
**Estimate:** Small

Create middleware for setting promotion context.

**Files:**
- `app/Http/Middleware/EnsurePromotionContext.php`
- `bootstrap/app.php` (register middleware)

**Acceptance Criteria:**
- [ ] Sets default promotion for authenticated users
- [ ] Handles users with no promotions
- [ ] Registered in web middleware group

---

### Task 4.2: Create Promotion Switcher Component
**Priority:** Medium
**Estimate:** Medium

Create Livewire component for switching promotions.

**Files:**
- `app/Livewire/PromotionSwitcher.php`
- `resources/views/livewire/promotion-switcher.blade.php`

**Acceptance Criteria:**
- [ ] Displays current promotion
- [ ] Lists user's promotions
- [ ] Switches context on selection
- [ ] Redirects to dashboard after switch

---

## Phase 5: Testing

### Task 5.1: Create Promotion Factory
**Priority:** High
**Estimate:** Small

Create factory for Promotion model.

**Files:**
- `database/factories/PromotionFactory.php`

**Acceptance Criteria:**
- [ ] Generates valid promotion data
- [ ] Creates associated user by default
- [ ] State for custom settings

---

### Task 5.2: Create Promotion Unit Tests
**Priority:** High
**Estimate:** Medium

Unit tests for Promotion model.

**Files:**
- `tests/Unit/Models/PromotionTest.php`

**Acceptance Criteria:**
- [ ] Tests all relationships
- [ ] Tests settings casting
- [ ] Tests slug uniqueness

---

### Task 5.3: Create Scoping Integration Tests
**Priority:** High
**Estimate:** Medium

Integration tests for promotion scoping.

**Files:**
- `tests/Feature/PromotionScopingTest.php`

**Acceptance Criteria:**
- [ ] Tests entity scoping by promotion
- [ ] Tests context switching
- [ ] Tests auto-assignment on create
- [ ] Tests cross-promotion isolation

---

### Task 5.4: Update Entity Factory Tests
**Priority:** Medium
**Estimate:** Medium

Update existing entity tests for promotion context.

**Files:**
- All entity test files

**Acceptance Criteria:**
- [ ] Tests set promotion context
- [ ] Factories create with promotion
- [ ] Existing tests still pass

---

## Phase 6: Data Migration (If Existing Data)

### Task 6.1: Create Data Migration Script
**Priority:** High (if needed)
**Estimate:** Medium

Migrate existing data to promotion structure.

**Files:**
- `database/migrations/YYYY_MM_DD_migrate_existing_data_to_promotions.php`

**Acceptance Criteria:**
- [ ] Creates default promotion per user
- [ ] Assigns all user's entities to promotion
- [ ] Handles orphaned entities
- [ ] Reversible migration

---

## Future Enhancements (Out of Scope)

- [ ] Cross-promotion talent sharing/loans
- [ ] Cross-promotion events
- [ ] Shared venues across promotions
- [ ] Promotion templates/presets
- [ ] Promotion import/export
