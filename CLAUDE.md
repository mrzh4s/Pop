# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Pop Framework** is a custom PHP framework implementing **Vertical Slice Architecture (VSA)** with Inertia.js integration for building modern PHP + React/Vue applications. The framework is deliberately minimal with zero external dependencies (except PHPUnit for testing).

## Commands

### Development
```bash
# Install dependencies
composer install
npm install

# Start development server (requires web server configuration)
php -S localhost:8000 -t public

# Start Vite dev server for frontend
npm run dev

# Build frontend assets
npm run build
```

### Database
```bash
# Migrations are auto-run on bootstrap - no manual migration command needed
# Database location: Infrastructure/persistence/database/app.db
```

### Code Quality
```bash
# Run tests (when test suite is created)
./vendor/bin/phpunit

# Enable error reporting (already enabled in public/index.php)
# No linting/formatting tools configured yet
```

## Architecture

### Vertical Slice Architecture (VSA)

Each feature is self-contained with all its logic in one directory:

```
Features/
└── Auth/
    ├── Login/
    │   ├── LoginCommand.php      # Input DTO
    │   ├── LoginHandler.php      # Business logic
    │   ├── LoginController.php   # HTTP layer
    │   └── LoginResponse.php     # Output DTO
    └── Shared/
        ├── Domain/               # Domain models (User, UserDetails)
        ├── Ports/                # Interfaces (UserRepositoryInterface)
        ├── Adapters/             # Implementations (PgUserRepository)
        └── Exceptions/           # Feature-specific exceptions
```

**Key Principles:**
- Features are independent vertical slices, not layered
- Shared code goes in `Features/[Feature]/Shared/`
- Ports & Adapters pattern for clean dependencies
- Domain logic in Handlers, HTTP concerns in Controllers

### Directory Structure

- **`/Framework`** - Core framework classes (Bootstrap, Router, Database, Security, etc.)
- **`/Features`** - Business features as vertical slices
- **`/Infrastructure`** - Infrastructure concerns (routes, persistence, views)
- **`/Config`** - Configuration files (auto-discovered from env vars)
- **`/public`** - Web root with index.php entry point
- **`/docs`** - SQL schemas and documentation

### Bootstrap Flow

1. **public/index.php** defines ROOT_PATH and loads Framework/Bootstrap.php
2. **Bootstrap.php** auto-loads in this order:
   - Core classes (Environment, Configuration, Session, Security, etc.)
   - Database components (Connection, Migration)
   - Auth components (Permission, Activity, Traffic)
   - Application components (ViewEngine, Inertia, Router)
   - Service classes (auto-discovered from /services if exists)
   - Helper files (15 helpers with dependency-aware loading)
3. **Routes** loaded from Infrastructure/Http/Routes/{web,api}.php
4. **Router** dispatches to Controllers

### Database Architecture

**Multi-Database with Auto-Discovery:**
- Environment variables pattern: `{NAME}_DB_HOST`, `{NAME}_DB_PORT`, etc.
- Example: `MAIN_DB_HOST` creates a 'main' connection
- Access via: `DB::connection('main')`
- Supports: SQLite, PostgreSQL, MySQL, SQL Server

**Current Setup:**
- Default: SQLite at `Infrastructure/persistence/database/app.db`
- Auth schema via ATTACH DATABASE pattern
- Migrations auto-run on bootstrap from `Infrastructure/persistence/migrations/`

### Inertia.js Integration

**Custom Inertia Adapter** (no Composer dependency):
- Located in `Framework/View/Inertia.php`
- Helper function: `inertia('ComponentName', $props)`
- SSR support via Node.js server on port 13714
- Asset versioning built-in
- Lazy prop loading support

**Frontend (React + Tailwind CSS v4):**
- Entry point: `resources/js/app.jsx`
- Build system: Vite (vite.config.js)
- Dev server: localhost:5173
- Build output: `public/build/`
- Path alias: `@` → `resources/js/`

### Helper Functions

15 auto-loaded global helpers:

```php
env($key, $default)              // Environment variables
config($key, $default)           // Configuration values
session($key, $default)          // Session data
request($key, $default)          // Request data (auto-detects JSON/form)
view($template, $data)           // Render Blade templates
inertia($component, $props)      // Render Inertia components
route($name, $params)            // Generate named route URLs
can($permission)                 // Check user permissions
redirect($routeName)             // Redirect to named route
csrf_token()                     // Get CSRF token
csrf_field()                     // CSRF hidden input field
```

### Routing System

**Route Definition:**
```php
// In Infrastructure/Http/Routes/web.php or api.php
use Framework\Http\Router;

Router::get('/path', 'ControllerName@method', ['middleware'])->name('route.name');
Router::post('/api/endpoint', 'ControllerName@action', ['auth']);
```

**Built-in Middleware:**
- `auth` - Requires authenticated user
- `guest` - Only for non-authenticated users
- `public` - Accessible to all
- `permission:permission.name` - Requires specific permission
- `role:rolename` - Requires specific role

**Controller Naming:**
- Page controllers: `LoginPage`, `DashboardPage`, `MapPage`
- Methods: `show()` for rendering, action names for POST handlers

### Middleware System

**Auto-Discovery:**
Middleware classes are automatically discovered from:
- `Infrastructure/Http/Middleware/` - Global middleware
- `Features/*/Middleware/` - Feature-specific middleware

**Naming Convention:**
- File: `{Name}Middleware.php`
- Class: `{Name}Middleware`
- Route usage: Derived from class name (e.g., `AuthMiddleware` → `auth`)

**Creating Middleware:**

1. **Global Middleware** (Infrastructure/Http/Middleware/):
```php
<?php
namespace Infrastructure\Http\Middleware;

class AuthMiddleware
{
    public function handle()
    {
        if (!session('authenticated')) {
            redirect('auth.signin');
            return false; // Halt request
        }
        return true; // Continue
    }
}
```

2. **Feature-Specific Middleware** (Features/{Feature}/Middleware/):
```php
<?php
namespace Features\Auth\Middleware;

class AdminMiddleware
{
    public function handle()
    {
        if (!can('system.admin')) {
            header("Location: /dashboard", true, 302);
            exit;
        }
        return true;
    }
}
```

**Usage in Routes:**
```php
// Simple middleware (no parameters)
Router::get('/admin/users', 'UserPage@index', ['auth', 'admin']);

// Parameterized middleware
Router::get('/posts', 'PostPage@index', ['permission:posts.view']);
Router::post('/posts', 'PostPage@create', ['permission:posts.create']);
Router::get('/admin/settings', 'SettingsPage@index', ['role:admin']);
```

**Parameterized Middleware:**

Middleware can accept parameters using colon (`:`) syntax:

```php
// Format: 'middleware:param1:param2:param3'
['permission:users.view']          // Check permission "users.view"
['permission:system.admin']        // Check permission "system.admin"
['role:admin']                     // Check if user has admin role
['role:manager']                   // Check if user has manager role
```

**Creating Parameterized Middleware:**

```php
<?php
namespace Infrastructure\Http\Middleware;

class PermissionMiddleware
{
    // Use variadic parameters to accept multiple params
    public function handle(...$permissions)
    {
        if (!session('authenticated')) {
            redirect('auth.signin');
            return false;
        }

        $permissionString = implode('.', $permissions);

        if (!can($permissionString)) {
            header("Location: /dashboard", true, 302);
            exit;
        }

        return true;
    }
}
```

**Middleware Rules:**
- Must have a `handle()` method or be invokable (`__invoke`)
- Can accept parameters: `handle($param1, $param2, ...)`
- Return `false` to halt the request
- Return `true` or `void` to continue
- Can redirect or exit directly

### Permission System

**Role Hierarchy** (defined in Config/permissions.php):
- Superadmin → Corridor → Manager → Officer
- Roles inherit permissions from lower levels

**Permission Checking:**
```php
can('system.admin')              // Check specific permission
can('users.create')              // Dot notation for namespaced permissions
can('*')                         // All authenticated users
```

## Adding New Features

1. **Create Feature Directory:**
   ```
   Features/YourFeature/
   ├── Action/
   │   ├── ActionCommand.php
   │   ├── ActionHandler.php
   │   ├── ActionController.php
   │   └── ActionResponse.php
   └── Shared/
       └── Domain/
   ```

2. **Create Controller** in Features/YourFeature/Action/:
   ```php
   namespace Features\YourFeature\Action;

   class ActionController {
       public function handle(ActionCommand $command): ActionResponse {
           $handler = new ActionHandler();
           return $handler->execute($command);
       }
   }
   ```

3. **Add Routes** in Infrastructure/Http/Routes/:
   ```php
   use Framework\Http\Router;

   Router::get('/feature/action', 'ActionController@handle', ['auth'])
       ->name('feature.action');
   ```

4. **Framework Auto-Discovery** handles the rest via PSR-4 autoloading

## Database Patterns

**Connection Setup (.env):**
```env
APP_DB=Infrastructure/persistence/database/app.db
MAIN_DB_DRIVER=sqlite
MAIN_DB_DATABASE=Infrastructure/persistence/database/app.db
```

**Usage:**
```php
$db = DB::connection('app');  // or 'main', 'source', etc.
$stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
```

**Migrations:**
- Place SQL files in `Infrastructure/persistence/migrations/`
- Naming: `001_create_users_table.sql`, `002_add_permissions.sql`
- Auto-executed on bootstrap in numeric order

## Security Features

- **CSRF Protection**: Auto-enabled for POST/PUT/DELETE routes
- **Session Security**: Device tracking, IP validation
- **Password Hashing**: bcrypt via PHP password_hash()
- **Login Throttling**: Attempts tracked in auth.login_attempts
- **Permission System**: RBAC with role inheritance

## Key Technical Decisions

1. **Zero External Dependencies** - Everything built in-house except PHPUnit
2. **Environment-First Config** - All config auto-discovered from env vars
3. **VSA Over MVC** - Features are vertical slices, not horizontal layers
4. **Custom Inertia Adapter** - No official Inertia PHP dependency
5. **SQLite Primary** - Simple deployment, multi-schema via ATTACH DATABASE
6. **Auto-Discovery Bootstrap** - Classes, services, helpers auto-loaded
7. **PHP 8.4 Strict** - Modern PHP with strict typing, readonly properties

## Important Notes

- **Migrations are automatic** - No need to run migration commands
- **Helpers are global** - Available everywhere after bootstrap
- **Routes use named routes** - Always use `route('name')` not hardcoded paths
- **Request auto-detects JSON** - `request()` helper handles both JSON and form data
- **Controllers are thin** - Business logic belongs in Handlers, not Controllers
- **Shared code in Features/[Feature]/Shared/** - Never create a global "shared" directory
- **No timestamps in migrations** - Use numeric prefixes: 001_, 002_, etc.
