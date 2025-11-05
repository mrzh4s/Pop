# Pop Framework - Database Schema Structure

This document describes how the Pop Framework organizes database schemas to separate concerns across different database engines.

## Schema Organization Pattern

The framework follows a **schema separation pattern** inspired by domain-driven design:

- **PostgreSQL**: Uses database schemas (`auth`, `log`, `traffic`, etc.)
- **SQLite**: Uses `ATTACH DATABASE` to simulate schemas with separate database files

**Both databases now use the same schema notation:**
- PostgreSQL: `auth.users`, `log.user_activities`, `traffic.api_traffic`
- SQLite: `auth.users`, `log.user_activities`, `traffic.api_traffic`

This makes code **100% portable** between PostgreSQL and SQLite!

## Current Schema Structure

### 1. Auth Schema (PostgreSQL) / Auth Tables (SQLite)

**Location:**
- PostgreSQL: `docs/auth/auth.table.sql` and `docs/auth/auth.seed.sql`
- SQLite: N/A (not yet created)

**Purpose:** Handles authentication, authorization, and user management

**Tables under `auth` schema:**

#### User Management
- `auth.users` - User accounts
- `auth.user_details` - Extended user information
- `auth.sessions` - User sessions
- `auth.verification_codes` - Email/phone verification

#### Role & Group Management
- `auth.roles` - User roles (super_admin, admin, manager, staff, etc.)
- `auth.groups` - User groups (admin, client, authority, vendor, board)
- `auth.role_user` - User-role assignments
- `auth.group_user` - User-group assignments

#### Permission System
- `auth.permissions` - Individual permissions (users.create, projects.view, etc.)
- `auth.role_hierarchies` - Role inheritance (admin inherits from manager)
- `auth.role_permissions` - Role-permission assignments
- `auth.user_permissions` - Direct user-permission overrides
- `auth.permission_attributes` - Permission metadata/conditions
- `auth.permission_settings` - Permission system configuration
- `auth.permission_audit_log` - Permission change audit trail

#### Security
- `auth.login_attempts` - Failed login tracking
- `auth.password_resets` - Password reset tokens

### 2. Core Schema (SQLite Only - Current)

**Location:**
- SQLite: `apps/database/migrations/*.sql`

**Purpose:** Core application tables for the Pop Framework

**Current Tables:**

**Auth Tables (Migration 000):**
- `users` - User accounts
- `user_details` - Extended user information
- `groups` - User groups (admin, client, authority, vendor, board)
- `role_user` - User-role assignments
- `group_user` - User-group assignments
- `sessions` - User sessions with device tracking
- `login_attempts` - Failed login tracking
- `password_resets` - Password reset tokens
- `verification_codes` - Email/phone verification

**Permission Tables (Migration 001):**
- `roles` - Role definitions
- `permissions` - Permission definitions
- `role_hierarchies` - Role inheritance
- `role_permissions` - Role-permission mapping
- `user_permissions` - Direct user permissions
- `permission_attributes` - Permission attributes
- `permission_settings` - Permission settings
- `permission_audit_log` - Audit log

**Activity Tables (Migration 002):**
- `user_activities` - User activity logs
- `project_activities` - Project workflow tracking

**Traffic Tables (Migration 003):**
- `api_traffic` - API request/response monitoring

## Key Design Principles

### 1. Separate Concerns with Schemas (PostgreSQL)

**Good Pattern:**
```sql
-- Authentication & Authorization under auth schema
CREATE SCHEMA IF NOT EXISTS auth;
CREATE TABLE auth.users (...);
CREATE TABLE auth.roles (...);
CREATE TABLE auth.permissions (...);

-- Future: Core application under core schema
CREATE SCHEMA IF NOT EXISTS core;
CREATE TABLE core.projects (...);
CREATE TABLE core.workflows (...);

-- Future: Logging under logs schema
CREATE SCHEMA IF NOT EXISTS logs;
CREATE TABLE logs.user_activities (...);
CREATE TABLE logs.api_traffic (...);
```

**Benefits:**
- Clear separation of concerns
- Easier to manage permissions per schema
- Better organization for large systems
- Schema-level isolation

### 2. Consistent Naming (SQLite)

**Good Pattern:**
```sql
-- SQLite doesn't support schemas, use clear table names
CREATE TABLE users (...);
CREATE TABLE roles (...);
CREATE TABLE permissions (...);
CREATE TABLE user_activities (...);
CREATE TABLE api_traffic (...);
```

**Avoid:**
```sql
-- Bad: Mixed prefixes are confusing
CREATE TABLE log.user_activities (...);  -- Don't mix schema notation
CREATE TABLE sys_api_traffic (...);      -- Inconsistent prefixes
CREATE TABLE sys_record_changelog (...); -- Unclear naming
```

### 3. Structure Pattern

Both PostgreSQL and SQLite migrations follow this pattern:

```sql
BEGIN [TRANSACTION];

-- 1. Drop tables (for re-runnable migrations)
DROP TABLE IF EXISTS [schema.]table_name;

-- 2. Create tables WITHOUT foreign keys
CREATE TABLE [schema.]table_name (...);

-- 3. Add indexes
CREATE INDEX idx_name ON [schema.]table_name(column);

-- 4. Add foreign key constraints (PostgreSQL only at end)
ALTER TABLE [schema.]table_name
    ADD CONSTRAINT fk_name FOREIGN KEY (...);

-- 5. Create views for convenience
CREATE VIEW [schema.]view_name AS ...;

-- 6. Insert seed data
INSERT INTO [schema.]table_name VALUES (...);

COMMIT;
```

## Migration System

### Auto-Migration on Startup

The framework automatically runs pending migrations when the application starts:

1. `apps/core/Migration.php` - Migration engine
2. `apps/database/migrations/*.sql` - SQLite migrations (numbered: 001_, 002_, etc.)
3. `docs/*/` - PostgreSQL schemas with seed data

**Configuration:**
```env
AUTO_MIGRATE=true  # Auto-run migrations on startup
```

**Usage:**
```php
// Check pending migrations
if (has_pending_migrations()) {
    echo "Please run migrations!";
}

// Run migrations
$result = run_migrations();

// Show status
show_migration_status();
```

## Current Migration Files

### SQLite Migrations (Auto-loaded)
```
apps/database/migrations/
├── 000_create_auth_tables.sql         # Auth system (users, groups, sessions)
├── 001_create_permissions_tables.sql  # RBAC system (roles, permissions)
├── 002_create_activity_tables.sql     # Activity logging
└── 003_create_traffic_tables.sql      # API traffic monitoring
```

**Migration Order:**
1. `000` - Creates auth tables (users, groups, sessions, login tracking)
2. `001` - Creates permission tables (depends on users from 000)
3. `002` - Creates activity tables (independent)
4. `003` - Creates traffic tables (independent)

### PostgreSQL Schemas (Manual execution)
```
docs/
├── auth/
│   ├── auth.table.sql      # Auth schema structure
│   ├── auth.seed.sql       # Auth seed data
│   └── auth.erd.png        # ER diagram
└── permission/
    ├── permission.table.sql  # Permission tables under auth schema
    └── permission.seed.sql   # Permission seed data
```

## Framework Support for Both Databases

The Pop Framework's core classes support both PostgreSQL and SQLite:

### Connection Class (`apps/core/Connection.php`)
```php
// Supports multiple database drivers
'connections' => [
    'main' => ['driver' => 'sqlite', 'database' => 'app.db'],
    'source' => ['driver' => 'pgsql', 'host' => 'localhost', ...],
]
```

### Permission Class (`apps/core/Permission.php`)
```php
// Works with both:
// - PostgreSQL: auth.permissions, auth.roles, etc.
// - SQLite: permissions, roles, etc.
```

## Future Schema Expansion

### Planned Schemas (PostgreSQL)

**Core Schema:**
```sql
CREATE SCHEMA IF NOT EXISTS core;
-- Business logic tables
CREATE TABLE core.projects (...);
CREATE TABLE core.workflows (...);
CREATE TABLE core.documents (...);
```

**Logs Schema:**
```sql
CREATE SCHEMA IF NOT EXISTS logs;
-- All logging tables
CREATE TABLE logs.user_activities (...);
CREATE TABLE logs.api_traffic (...);
CREATE TABLE logs.audit_trail (...);
```

**Config Schema:**
```sql
CREATE SCHEMA IF NOT EXISTS config;
-- System configuration
CREATE TABLE config.settings (...);
CREATE TABLE config.features (...);
```

## Best Practices

### 1. Always Use Transactions
```sql
BEGIN;
-- Your migration code
COMMIT;
```

### 2. Make Migrations Re-runnable
```sql
DROP TABLE IF EXISTS table_name;
CREATE TABLE IF NOT EXISTS table_name (...);
```

### 3. Use Descriptive Names
- `user_activities` NOT `log.activities` or `sys_log`
- `api_traffic` NOT `sys_api_traffic`
- `project_activities` NOT `sys_record_changelog`

### 4. Document Each Migration
```sql
-- ============================================================
-- Clear Title
-- File: path/to/file.sql
-- Description of what this migration does
-- ============================================================
```

### 5. Create Helpful Views
```sql
-- Make complex queries easier
CREATE VIEW user_summary AS
SELECT u.*, COUNT(r.id) as role_count
FROM users u
LEFT JOIN user_roles r ON u.id = r.user_id
GROUP BY u.id;
```

## Schema Comparison

| Feature | PostgreSQL | SQLite |
|---------|-----------|--------|
| **Schemas** | ✅ Supported (`auth.users`) | ❌ Not supported |
| **Foreign Keys** | ✅ Full support | ⚠️ Limited support |
| **Views** | ✅ Full support | ✅ Supported |
| **Transactions** | ✅ Full ACID | ✅ Supported |
| **UUID** | ✅ Native type | ❌ Use TEXT |
| **JSONB** | ✅ Native type | ❌ Use TEXT |
| **Indexes** | ✅ Advanced | ✅ Basic |
| **Use Case** | Production, multi-database | Development, embedded |

## Summary

The Pop Framework uses:

1. **PostgreSQL with Schemas** - For production environments with clear separation of concerns
   - `auth` schema for authentication and authorization
   - Future: `core`, `logs`, `config` schemas

2. **SQLite without Schemas** - For development and embedded applications
   - Standard table names (permissions, roles, users, etc.)
   - Same logical structure, different physical implementation

3. **Auto-Migration System** - Automatically applies database changes on startup

4. **Consistent Naming** - No confusing prefixes, clear standard names

This approach gives you:
- ✅ Flexibility to use either database
- ✅ Clear separation of concerns
- ✅ Easy to understand and maintain
- ✅ Production-ready from day one
