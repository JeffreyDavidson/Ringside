# Implementation Tasks: Users System

> Reference: @.agent-os/specs/2026-01-16-users-system/spec.md

---

## Phase 1: Enums

### Task 1.1: Create Role Enum
**Priority:** Critical
**Estimate:** Small

Create the Role enum for user roles.

**Files:**
- `app/Enums/Role.php`

**Acceptance Criteria:**
- [ ] Enum has Admin and Promoter cases
- [ ] label() method returns display name
- [ ] isAdmin() helper method

---

### Task 1.2: Create UserStatus Enum
**Priority:** Critical
**Estimate:** Small

Create the UserStatus enum for account status.

**Files:**
- `app/Enums/UserStatus.php`

**Acceptance Criteria:**
- [ ] Enum has Active, Suspended, Pending cases
- [ ] label() method returns display name
- [ ] canLogin() helper method

---

## Phase 2: Database Updates

### Task 2.1: Add Role and Status to Users Table
**Priority:** Critical
**Estimate:** Small

Migration to add role and status columns.

**Files:**
- `database/migrations/YYYY_MM_DD_add_role_status_to_users_table.php`

**Acceptance Criteria:**
- [ ] Adds role column with default 'promoter'
- [ ] Adds status column with default 'active'
- [ ] Indexes on both columns
- [ ] Backfills existing users

---

## Phase 3: Model Updates

### Task 3.1: Update User Model
**Priority:** Critical
**Estimate:** Medium

Update User model with new fields and relationships.

**Files:**
- `app/Models/User.php`

**Acceptance Criteria:**
- [ ] role and status added to fillable
- [ ] Casts for Role and UserStatus enums
- [ ] promotions() relationship added
- [ ] currentPromotion() accessor added
- [ ] isAdmin() helper method
- [ ] canLogin() helper method

---

### Task 3.2: Remove Wrestler Relationship from User
**Priority:** Critical
**Estimate:** Small

Remove direct wrestler ownership from User model.

**Files:**
- `app/Models/User.php`

**Acceptance Criteria:**
- [ ] wrestlers() relationship removed
- [ ] Any references to User->wrestlers updated
- [ ] Tests updated to use Promotion->wrestlers

**Breaking Change:** This removes `User::wrestlers()`. All code must use `Promotion::wrestlers()` instead.

---

## Phase 4: Factory Updates

### Task 4.1: Update UserFactory
**Priority:** High
**Estimate:** Small

Add role and status states to UserFactory.

**Files:**
- `database/factories/UserFactory.php`

**Acceptance Criteria:**
- [ ] Default role is Promoter
- [ ] Default status is Active
- [ ] admin() state method
- [ ] promoter() state method
- [ ] suspended() state method
- [ ] pending() state method

---

## Phase 5: Authentication Updates

### Task 5.1: Update Login Validation
**Priority:** High
**Estimate:** Small

Add status check to login process.

**Files:**
- `app/Http/Requests/Auth/LoginRequest.php`

**Acceptance Criteria:**
- [ ] Check user status before authentication
- [ ] Return appropriate error for suspended users
- [ ] Return appropriate error for pending users

---

### Task 5.2: Add Authorization Gates
**Priority:** Medium
**Estimate:** Small

Create gates for admin and promotion access.

**Files:**
- `app/Providers/AppServiceProvider.php`

**Acceptance Criteria:**
- [ ] 'admin' gate for admin-only actions
- [ ] 'manage-promotion' gate for promotion ownership

---

## Phase 6: Testing

### Task 6.1: Create User Unit Tests
**Priority:** High
**Estimate:** Medium

Unit tests for User model.

**Files:**
- `tests/Unit/Models/UserTest.php`

**Acceptance Criteria:**
- [ ] Tests role enum casting
- [ ] Tests status enum casting
- [ ] Tests promotions relationship
- [ ] Tests isAdmin() helper
- [ ] Tests canLogin() helper
- [ ] Tests that wrestlers() relationship does NOT exist

---

### Task 6.2: Create Enum Unit Tests
**Priority:** Medium
**Estimate:** Small

Unit tests for Role and UserStatus enums.

**Files:**
- `tests/Unit/Enums/RoleTest.php`
- `tests/Unit/Enums/UserStatusTest.php`

**Acceptance Criteria:**
- [ ] Tests all enum cases
- [ ] Tests label() methods
- [ ] Tests helper methods

---

### Task 6.3: Update Authentication Tests
**Priority:** High
**Estimate:** Medium

Update auth tests for status checking.

**Files:**
- `tests/Feature/Auth/AuthenticationTest.php`

**Acceptance Criteria:**
- [ ] Test active user can login
- [ ] Test suspended user cannot login
- [ ] Test pending user cannot login
- [ ] Test admin role check works

---

## Phase 7: Codebase Updates

### Task 7.1: Update Wrestler Creation References
**Priority:** Critical
**Estimate:** Medium

Update any code that creates wrestlers via User relationship.

**Files:**
- Various controllers, services, tests

**Acceptance Criteria:**
- [ ] Find all `$user->wrestlers()->create()` usages
- [ ] Update to `$promotion->wrestlers()->create()`
- [ ] Update related tests

---

### Task 7.2: Update Dashboard/Profile Views
**Priority:** Medium
**Estimate:** Small

Update views that display user's wrestlers.

**Files:**
- Dashboard views
- Profile views

**Acceptance Criteria:**
- [ ] Update to use promotion context
- [ ] Display promotion selection if multiple
- [ ] Show wrestler count from promotion

---

## Summary

| Phase | Tasks | Priority |
|-------|-------|----------|
| 1. Enums | 2 | Critical |
| 2. Database | 1 | Critical |
| 3. Models | 2 | Critical |
| 4. Factory | 1 | High |
| 5. Auth | 2 | High/Medium |
| 6. Testing | 3 | High/Medium |
| 7. Codebase | 2 | Critical/Medium |

**Total Tasks:** 13

**Breaking Changes:**
- `User::wrestlers()` relationship removed
- All wrestler creation must go through Promotion
