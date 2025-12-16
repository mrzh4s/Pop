# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## About Pop Framework

Pop Framework is a lightweight PHP framework designed to bridge old and new coding practices with a simple structure and modern features. It supports both PostgreSQL and SQLite with 100% portable code using schema notation.

## Technology Stack

- **PHP**: 8.4+ (backend framework)
- **Frontend**: Tailwind CSS 4, TypeScript, Webpack
- **Databases**: SQLite (development), PostgreSQL (production)
- **Build Tools**: npm, webpack, Tailwind CLI

## Build and Development Commands

### Frontend Development
```bash
# Development mode with hot reload
npm run dev                  # Run CSS and JS watchers in parallel
npm run dev:css              # Watch CSS changes only
npm run dev:js               # Watch JS changes only

# Build commands
npm run build                # Build CSS and JS for development
npm run build:css            # Build CSS only
npm run build:js             # Build JS only
npm run build:prod           # Build for production (minified)

# Code quality
npm run lint                 # Lint TypeScript files
```

### PHP Development
The framework has no CLI commands. PHP runs through a web server (Apache/nginx).

**Access points:**
- Main entry: `/apps/index.php`
- Bootstrap: `/apps/core/bootstrap.php`
- Routes: `/apps/routes.php`

## Core Architecture

### 1. Bootstrap System (`apps/core/bootstrap.php`)

The framework uses an auto-discovery bootstrap system that loads classes and helpers in dependency order:

**Load Order:**
1. **Core Classes** (in `apps/core/`):
   - Environment → Configuration → Session → Cookie → Security
   - Connection → Migration
   - Permission → Activity → Traffic
   - ViewEngine → Router → Curl

2. **Service Classes** (in `apps/services/`):
   - Auto-discovered after core classes

3. **Helper Functions** (in `apps/core/helpers/`):
   - Loaded after their corresponding classes
   - Example: `permission.php` loads after `Permission.php`

### 2. Routing System (`apps/core/Router.php`)

**Smart Auto-Detection:**
- Routes automatically detect pages in `apps/pages/` folder
- Supports nested folder structures
- No prefix needed for page names

**Route Definition:**
```php
// In apps/routes/web.php or apps/routes/api.php
$router->get('/path', 'PageClass@method', 'route.name');
$router->post('/path', 'PageClass@method');
```

**Middleware:**
```php
$router->middleware('auth', 'authMiddleware');
$router->middleware('guest', 'guestMiddleware');
```

### 3. Multi-Database System (`apps/core/Connection.php`)

**Configuration:** `apps/config/Database.php`

The framework supports unlimited named database connections:

```php
// Access different databases
$main = DB::connection();              // Default connection
$source = DB::connection('source');    // PostgreSQL source
$dest = DB::connection('dest');        // PostgreSQL destination
$analytics = DB::connection('analytics'); // Custom connection
```

**Supported Drivers:**
- `sqlite` - Development and embedded
- `pgsql` / `postgres` / `postgresql` - Production
- `mysql` - MySQL/MariaDB
- `sqlsrv` / `mssql` - SQL Server

### 4. Schema Organization Pattern

**Critical: Both PostgreSQL and SQLite use identical schema notation**

**PostgreSQL:**
```sql
CREATE SCHEMA IF NOT EXISTS auth;
CREATE TABLE auth.users (...);
CREATE TABLE auth.permissions (...);
```

**SQLite (using ATTACH DATABASE):**
```sql
ATTACH DATABASE 'database/auth.db' AS auth;
CREATE TABLE auth.users (...);
CREATE TABLE auth.permissions (...);
```

**Schema Structure:**
- `auth` schema: Authentication, authorization, users, roles, permissions
- `log` schema: Activity logging (`log.user_activities`, `log.project_activities`)
- `traffic` schema: API traffic monitoring (`traffic.api_traffic`)

This makes code 100% portable between databases!

### 5. Migration System (`apps/core/Migration.php`)

**Auto-Migration on Startup:**
Set `AUTO_MIGRATE=true` in `.env` to run migrations automatically on app start.

**Migration Files:** `apps/database/migrations/*.sql`

**Naming Convention:** `000_`, `001_`, `002_`, `003_` prefix for execution order

**Current Migrations:**
- `000_create_auth_tables.sql` - Auth system (users, groups, sessions)
- `001_create_permissions_tables.sql` - RBAC (roles, permissions)
- `002_create_activity_tables.sql` - Activity logs
- `003_create_traffic_tables.sql` - API traffic

**Migration Pattern:**
```sql
BEGIN TRANSACTION;

-- 1. ATTACH DATABASE for schema (SQLite only)
ATTACH DATABASE 'database/auth.db' AS auth;

-- 2. Drop tables (for re-runnable migrations)
DROP TABLE IF EXISTS auth.users;

-- 3. Create tables
CREATE TABLE IF NOT EXISTS auth.users (...);

-- 4. Add indexes
CREATE INDEX IF NOT EXISTS idx_users_email ON auth.users(email);

-- 5. Create views (optional)
CREATE VIEW IF NOT EXISTS auth.user_summary AS ...;

-- 6. Insert seed data
INSERT INTO auth.users VALUES (...);

COMMIT;
```

### 6. Permission System (`apps/core/Permission.php`)

**Dual-Source System:** Can load from config file OR database

**Configuration:** `apps/config/Permissions.php` or `auth.permission_settings` table

**Features:**
- Role-based access control (RBAC)
- Role hierarchy with inheritance
- Direct user permission overrides
- Attribute-based access control (ABAC)
- Permission caching

**Database Tables:**
- `auth.roles` - Role definitions
- `auth.permissions` - Permission definitions
- `auth.role_hierarchies` - Role inheritance
- `auth.role_permissions` - Role-permission mapping
- `auth.user_permissions` - Direct user overrides

### 7. Activity & Traffic Logging

**Activity Logger** (`apps/core/Activity.php`):
- Tracks user activities with IP, location, device info
- Tables: `log.user_activities`, `log.project_activities`

**Traffic Logger** (`apps/core/Traffic.php`):
- Monitors API requests/responses
- Table: `traffic.api_traffic`
- Tracks: URL, method, headers, body, response time, status codes

## Directory Structure

```
/var/www/Pop/
├── apps/                           # PHP application code
│   ├── core/                       # Core framework classes
│   │   ├── bootstrap.php          # Auto-discovery bootstrap
│   │   ├── Router.php             # Smart routing system
│   │   ├── Connection.php         # Multi-database support
│   │   ├── Permission.php         # RBAC system
│   │   ├── Migration.php          # Auto-migration engine
│   │   ├── Activity.php           # Activity logging
│   │   ├── Traffic.php            # API traffic monitoring
│   │   └── helpers/               # Helper functions
│   ├── config/                    # Configuration files
│   │   ├── Database.php           # Database connections
│   │   └── Permissions.php        # Permission config
│   ├── database/                  # Database files
│   │   └── migrations/            # Migration SQL files
│   ├── pages/                     # Page controllers
│   ├── routes/                    # Route definitions
│   │   ├── web.php               # Web routes
│   │   └── api.php               # API routes
│   ├── templates/                 # View templates
│   ├── components/                # Reusable components
│   ├── services/                  # Business logic services
│   └── index.php                  # Main entry point
├── docs/                          # Documentation & SQL schemas
│   ├── auth/                      # PostgreSQL auth schema
│   │   ├── auth.table.sql
│   │   └── auth.seed.sql
│   ├── permission/                # PostgreSQL permission schema
│   │   ├── permission.table.sql
│   │   └── permission.seed.sql
│   └── SCHEMA_STRUCTURE.md        # Schema documentation
├── src/                           # Frontend TypeScript source
├── node_modules/                  # npm dependencies
├── package.json                   # npm configuration
└── webpack.config.js              # Webpack configuration
```

## Important Patterns

### Creating New Migrations

1. **Naming:** Use sequential numbering: `004_description.sql`
2. **Schema Attachment:** Always attach the appropriate schema
3. **Transaction Wrapping:** Wrap in BEGIN/COMMIT
4. **Idempotent:** Use `DROP TABLE IF EXISTS` and `CREATE TABLE IF NOT EXISTS`

**Example:**
```sql
BEGIN TRANSACTION;

-- Attach schema
ATTACH DATABASE 'database/auth.db' AS auth;

-- Drop if exists
DROP TABLE IF EXISTS auth.new_table;

-- Create table
CREATE TABLE IF NOT EXISTS auth.new_table (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ...
);

-- Add indexes
CREATE INDEX IF NOT EXISTS idx_new_table_field ON auth.new_table(field);

COMMIT;
```

### Adding New Database Connections

Edit `apps/config/Database.php`:

```php
'connections' => [
    'new_connection' => [
        'driver' => 'pgsql',
        'host' => env('NEW_DB_HOST', 'localhost'),
        'port' => env('NEW_DB_PORT', 5432),
        'database' => env('NEW_DB_DATABASE', 'dbname'),
        'username' => env('NEW_DB_USERNAME', 'user'),
        'password' => env('NEW_DB_PASSWORD', 'pass'),
    ],
]
```

Use it:
```php
$conn = DB::connection('new_connection');
```

### Using Schema Notation in Queries

Always use schema notation for portability:

```php
// Good - Works with both PostgreSQL and SQLite
$stmt = $db->query("SELECT * FROM auth.users WHERE email = ?");
$stmt = $db->query("INSERT INTO log.user_activities (user_id, message) VALUES (?, ?)");
$stmt = $db->query("SELECT * FROM traffic.api_traffic WHERE status = 'error'");

// Bad - Not portable
$stmt = $db->query("SELECT * FROM users WHERE email = ?");
```

### Environment Variables

Located in `apps/.env`. Example structure in `apps/.env.example`.

**Key Variables:**
- `DB_DEFAULT` - Default database connection name
- `APP_DB` - SQLite database path
- `AUTO_MIGRATE` - Auto-run migrations on startup
- `APP_DEBUG` - Enable debug mode
- `SOURCE_DB_*` - PostgreSQL source connection
- `DEST_DB_*` - PostgreSQL destination connection

## PostgreSQL Schema Files

Located in `docs/` directory for manual execution in production PostgreSQL:

- `docs/auth/auth.table.sql` - Auth schema structure
- `docs/auth/auth.seed.sql` - Auth seed data
- `docs/permission/permission.table.sql` - Permission tables
- `docs/permission/permission.seed.sql` - Permission seed data

These are PostgreSQL-specific and separate from SQLite migrations.

## Key Documentation

- `docs/SCHEMA_STRUCTURE.md` - Complete schema organization guide
- `README.md` - Project overview

## Notes

- The framework has no test suite currently
- Hot reload for frontend: Use `npm run dev`
- Database migrations run automatically if `AUTO_MIGRATE=true`
- All database queries should use schema notation for portability
- Permission system can use config OR database as source
