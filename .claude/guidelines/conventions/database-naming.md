# Database Naming Conventions

Table names, column names, and foreign key naming patterns for Ringside development.

## Overview

Consistent database naming ensures clear, maintainable database schemas.

## Table Names

### Table Naming Standards
- **Plural**: Use plural form for table names
- **Snake Case**: Use snake_case for all table names
- **Descriptive**: Include full entity names

```sql
-- ✅ CORRECT
CREATE TABLE wrestlers (...);
CREATE TABLE tag_teams (...);
CREATE TABLE wrestler_employments (...);
CREATE TABLE stable_members (...);

-- ❌ INCORRECT
CREATE TABLE Wrestlers (...);            -- Not snake_case
CREATE TABLE tagteams (...);             -- Missing underscore
CREATE TABLE emp (...);                  -- Abbreviated
```

## Column Names

### Column Naming Standards
- **Snake Case**: Use snake_case for all column names
- **Descriptive**: Use full, descriptive names
- **Consistent Patterns**: Follow established patterns

```sql
-- ✅ CORRECT
CREATE TABLE wrestlers (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    hometown VARCHAR(255),
    height_feet INT,
    height_inches INT,
    weight INT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- ❌ INCORRECT
CREATE TABLE wrestlers (
    ID BIGINT PRIMARY KEY,                -- Not snake_case
    nm VARCHAR(255),                      -- Abbreviated
    heightFeet INT,                       -- camelCase
    wt INT                                -- Abbreviated
);
```

## Foreign Key Naming

### Foreign Key Standards
- **Singular + _id**: Use singular entity name with "_id" suffix
- **Consistent**: Follow same pattern across all tables
- **Descriptive**: Include full entity name

```sql
-- ✅ CORRECT
ALTER TABLE wrestler_employments
    ADD CONSTRAINT fk_wrestler_employments_wrestler_id
    FOREIGN KEY (wrestler_id) REFERENCES wrestlers(id);

-- ❌ INCORRECT
ALTER TABLE wrestler_employments
    ADD CONSTRAINT fk_emp_wrestler
    FOREIGN KEY (w_id) REFERENCES wrestlers(id);
```

## Related Documentation
- [Naming Conventions](naming.md)
- [Structural Patterns](structure.md)
- [Database Standards](../database.md)
