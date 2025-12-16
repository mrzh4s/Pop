# Database Migrations

This directory contains SQLite database migration files that are automatically executed on application startup.

## Migration Files

Migrations are executed in numerical order:

### 000_create_auth_tables.sql
**Purpose:** Authentication and authorization system
**Creates:**
- `users` - User accounts with authentication
- `user_details` - Extended user profile information
- `groups` - Organizational groups (admin, client, authority, vendor, board)
- `role_user` - User-role assignments (many-to-many)
- `group_user` - User-group assignments (many-to-many)
- `sessions` - User sessions with device tracking
- `login_attempts` - Failed login tracking for security
- `password_resets` - Password reset token management
- `verification_codes` - Email/phone verification codes

**Default Data:**
- Creates 5 default groups
- Creates default admin user (email: `admin@system.local`, password: `password`)

**Views:**
- `user_summary` - Complete user information with roles and groups
- `group_composition` - Group membership statistics
- `active_sessions` - Currently active user sessions
- `user_login_stats` - User login statistics and security info
- `security_alerts` - Security-related alerts

### 001_create_permissions_tables.sql
**Purpose:** RBAC (Role-Based Access Control) permission system
**Depends on:** `000_create_auth_tables.sql` (requires users table)
**Creates:**
- `roles` - Role definitions with hierarchy levels
- `permissions` - Individual permission definitions
- `role_hierarchies` - Role inheritance relationships
- `role_permissions` - Role-permission assignments
- `user_permissions` - Direct user-permission overrides
- `permission_attributes` - Permission metadata
- `permission_settings` - System configuration
- `permission_audit_log` - Audit trail of permission changes

**Default Data:**
- Creates 7 default roles (super_admin, admin, manager, supervisor, staff, user, guest)
- Creates 37 default permissions across 7 modules
- Sets up role hierarchies (inheritance)
- Assigns permissions to roles based on levels

**Views:**
- `role_permissions_with_hierarchy` - Complete role permissions including inherited
- `user_permissions_complete` - All user permissions from roles and direct assignments
- `role_permission_summary` - Role statistics
- `permission_usage_stats` - Permission usage across system

### 002_create_activity_tables.sql
**Purpose:** User and project activity logging
**Depends on:** None (independent)
**Creates:**
- `user_activities` - User activity logs with IP, location, device info
- `project_activities` - Project workflow and changelog tracking

**Views:**
- `user_activity_summary` - Activity statistics per user
- `project_activity_timeline` - Project change history

### 003_create_traffic_tables.sql
**Purpose:** API traffic monitoring and analytics
**Depends on:** None (independent)
**Creates:**
- `api_traffic` - API request/response logs with performance metrics

**Views:**
- `traffic_statistics` - Traffic stats by type, method, status
- `endpoint_performance` - Endpoint performance metrics
- `hourly_traffic` - Hourly traffic patterns
- `recent_errors` - Recent error log

## Migration Execution

### Automatic Execution
Migrations run automatically on application startup when `AUTO_MIGRATE=true` in `.env`:

```env
AUTO_MIGRATE=true
```

The framework tracks executed migrations in the `migrations` table to prevent re-running.

### Manual Execution
You can also run migrations manually:

```php
// Check if migrations are needed
if (has_pending_migrations()) {
    echo "Migrations pending!";
}

// Run all pending migrations
$result = run_migrations();

// Show migration status
show_migration_status();

// Rollback last batch (tracking only in SQLite)
$result = rollback_migrations();
```

### Migration Status
```bash
# Via CLI
php -r "require 'core/bootstrap.php'; show_migration_status();"
```

## Migration Dependencies

**Critical Order:**
1. `000_create_auth_tables.sql` must run first
   - Creates `users` table required by other migrations

2. `001_create_permissions_tables.sql` depends on `000`
   - `role_user` table references `users` table
   - Default admin user gets assigned roles

3. `002` and `003` are independent
   - Can run in any order
   - Don't depend on each other

## PostgreSQL Equivalent

For PostgreSQL deployments with schema support, see:
- `docs/auth/auth.table.sql` - Auth schema (users, groups, sessions)
- `docs/auth/auth.seed.sql` - Auth seed data
- `docs/permission/permission.table.sql` - Permission tables under auth schema
- `docs/permission/permission.seed.sql` - Permission seed data

PostgreSQL uses the `auth` schema for all authentication and permission tables:
- `auth.users`, `auth.roles`, `auth.permissions`, etc.

## Best Practices

### 1. Never Edit Executed Migrations
Once a migration has been executed in production, never modify it. Create a new migration instead.

### 2. Use Transactions
All migrations use `BEGIN TRANSACTION` and `COMMIT` to ensure atomicity.

### 3. Make Migrations Idempotent
All migrations use `DROP TABLE IF EXISTS` and `CREATE TABLE IF NOT EXISTS` to be safely re-runnable.

### 4. Test Migrations
Always test migrations on a copy of production data before deploying.

### 5. Backup Before Running
```bash
# Backup your database before running migrations
cp database/app.db database/app.db.backup.$(date +%Y%m%d_%H%M%S)
```

## Migration Tracking

The system tracks migrations in the `migrations` table:

```sql
CREATE TABLE migrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    migration VARCHAR(255) UNIQUE NOT NULL,
    batch INTEGER NOT NULL,
    executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

Each execution is assigned a batch number, allowing rollback of related migrations together.

## Troubleshooting

### Migration Fails
1. Check error logs: `error_log()` outputs to PHP error log
2. Verify database permissions
3. Check for syntax errors in SQL
4. Ensure dependencies are met

### Reset Migrations (Development Only!)
```php
// WARNING: This drops all migration tracking
$result = reset_migrations();

// Then run migrations again
$result = run_migrations();
```

### Check What Will Run
```php
$pending = pending_migrations();
foreach ($pending as $migration) {
    echo "Will run: {$migration['migration']}\n";
}
```

## Default Credentials

**Admin User (created by migration 000):**
- Email: `admin@system.local`
- Password: `password`
- Groups: None (assign manually)
- Roles: None (assigned by migration 001 seed data)

**Security Note:** Change the default admin password immediately after first login!

## Views Available

After migrations, you have access to these helpful views:

**Auth Views:**
- `user_summary` - User info with roles and groups
- `group_composition` - Group membership
- `active_sessions` - Active sessions
- `user_login_stats` - Login statistics
- `security_alerts` - Security alerts

**Permission Views:**
- `role_permissions_with_hierarchy` - Role permissions with inheritance
- `user_permissions_complete` - All user permissions
- `role_permission_summary` - Role statistics
- `permission_usage_stats` - Permission usage

**Activity Views:**
- `user_activity_summary` - Activity per user
- `project_activity_timeline` - Project timeline

**Traffic Views:**
- `traffic_statistics` - Traffic stats
- `endpoint_performance` - Endpoint metrics
- `hourly_traffic` - Hourly patterns
- `recent_errors` - Recent errors

## Support

For more information:
- Framework docs: `/docs/SCHEMA_STRUCTURE.md`
- Standardization guide: `/STANDARDIZATION.md`
- Database patterns: `/docs/auth/`, `/docs/permission/`
