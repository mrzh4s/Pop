# Authentication System Documentation

This directory contains the complete PostgreSQL schema and seed data for the Pop Framework authentication system.

## Files

- **`auth.table.sql`** - Complete database schema with all tables, constraints, and indexes
- **`auth.seed.sql`** - Comprehensive seed data with users, roles, and groups

## Database Schema

### Tables

1. **`auth.users`** - Core user accounts
2. **`auth.user_details`** - Extended user profile information
3. **`auth.roles`** - User roles (super_admin, admin, manager, staff, etc.)
4. **`auth.groups`** - User groups (admin, client, authority, vendor, board)
5. **`auth.role_user`** - Many-to-many: Users ↔ Roles
6. **`auth.group_user`** - Many-to-many: Users ↔ Groups
7. **`auth.sessions`** - User session tracking with device info
8. **`auth.login_attempts`** - Failed login tracking for security
9. **`auth.password_resets`** - Password reset tokens
10. **`auth.verification_codes`** - Email/phone verification codes

## Installation

### 1. Create Database

```bash
createdb your_database_name
```

### 2. Enable UUID Extension

```sql
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
```

### 3. Run Schema

```bash
psql -d your_database_name -f docs/auth/auth.table.sql
```

### 4. Run Seed Data

```bash
psql -d your_database_name -f docs/auth/auth.seed.sql
```

## Default Users

All users have password: **`password`**

### Super Admin
- Email: `superadmin@kutt.my`
- Role: Super Administrator
- Full system access

### Admin Group
- `admin@kutt.my` - General Administrator
- `sarah.ahmad@kutt.my` - HR Manager
- `siti.nurhaliza@kutt.my` - Finance Manager
- `faiz.ibrahim@kutt.my` - IT Manager
- `nurul.aina@kutt.my` - Senior Staff
- `razak.hassan@kutt.my` - Staff
- `aishah.ali@intern.kutt.my` - Intern

### Other Groups
- `client@abcconstruction.my` - Client (Construction Company)
- `rahman.abdullah@authority.gov.my` - Authority (Government)
- `vendor@techsolutions.my` - Vendor (Tech Solutions)
- `board.chairman@kutt.my` - Board Member

## Roles Hierarchy

```
super_admin (Full Access)
├── admin
├── hr_manager
├── finance_manager
├── department_head
├── manager
├── supervisor
├── senior_staff
├── staff
├── intern
├── contractor
├── vendor
├── auditor
├── authority_user
├── client
├── board_member
├── viewer
└── guest
```

## Groups

1. **Admin** - Internal staff (HR, Finance, IT, Management)
2. **Client** - Road corridor applicants
3. **Authority** - Government officials and road authorities
4. **Vendor** - External vendors and suppliers
5. **Board** - Board of directors

## Security Features

### Session Management
- Device tracking (type, name, platform, browser)
- IP address logging
- Location tracking (city, country)
- Trusted device management
- Last activity timestamps

### Login Security
- Failed attempt tracking by IP + email
- Automatic account blocking after threshold
- Time-based unlock mechanism
- CSRF protection
- Password hashing (bcrypt)

### Password Reset
- Secure token generation
- Expiration timestamps
- Email-based verification

## Helper Views

### User Summary
```sql
SELECT * FROM auth.user_summary;
```
Shows complete user information with roles and groups.

### Group Composition
```sql
SELECT * FROM auth.group_composition;
```
Shows group membership and role distribution.

## Usage in Pop Framework

### Check User Permissions
```php
use Framework\Http\Request;

// Check if user has permission
if (can('system.admin')) {
    // User is admin
}

// Get current user
$userId = session('user_id');
$userEmail = session('user_email');
```

### Middleware Protection
```php
// In routes
Router::get('/admin', 'AdminController@index', ['auth', 'permission:system.admin']);
Router::get('/hr', 'HRController@index', ['role:hr_manager']);
```

### Session Data
```php
// User session data
session('authenticated')    // bool
session('user_id')          // UUID
session('user_email')       // string
session('user_name')        // string
session('user_role')        // string
session('user.role')        // string (alternative)
```

## Maintenance

### Add New User
```sql
INSERT INTO auth.users (name, email, password, is_active)
VALUES ('New User', 'user@example.com', '$2y$10$...', true);
```

### Add User to Role
```sql
INSERT INTO auth.role_user (user_id, role_id)
VALUES (
    (SELECT id FROM auth.users WHERE email = 'user@example.com'),
    (SELECT id FROM auth.roles WHERE name = 'staff')
);
```

### Add User to Group
```sql
INSERT INTO auth.group_user (user_id, group_id)
VALUES (
    (SELECT id FROM auth.users WHERE email = 'user@example.com'),
    (SELECT id FROM auth.groups WHERE name = 'admin')
);
```

### View Active Sessions
```sql
SELECT
    u.email,
    s.device_name,
    s.browser,
    s.ip_address,
    s.last_used_at,
    s.is_current
FROM auth.sessions s
JOIN auth.users u ON s.user_id = u.id
WHERE s.user_id = 'USER_UUID_HERE'
ORDER BY s.last_used_at DESC;
```

### Clear Old Sessions
```sql
DELETE FROM auth.sessions
WHERE last_activity < EXTRACT(EPOCH FROM (NOW() - INTERVAL '30 days'));
```

### Reset Failed Login Attempts
```sql
DELETE FROM auth.login_attempts
WHERE last_attempt < NOW() - INTERVAL '24 hours';
```

## Notes

- All tables use soft deletes where applicable
- Foreign keys cascade on delete for data integrity
- Indexes are created for optimal query performance
- Timestamps are in UTC
- UUIDs used for user IDs for security
- All passwords must be hashed using bcrypt ($2y$10$)

## Migration to SQLite

For SQLite compatibility, the schema needs these modifications:

1. Remove `uuid_generate_v4()` - generate UUIDs in PHP
2. Change `uuid` type to `TEXT`
3. Change `bigserial` to `INTEGER PRIMARY KEY AUTOINCREMENT`
4. Remove `SCHEMA` statements - SQLite doesn't support schemas
5. Change `inet` type to `TEXT`
6. Change `jsonb` to `TEXT` (store JSON as string)
7. Simplify `ON CONFLICT` clauses

## Support

For issues or questions, refer to the Pop Framework documentation or raise an issue in the repository.
