# Pop Framework

> Modern PHP framework implementing Vertical Slice Architecture with Inertia.js integration

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.4%2B-blue.svg)](https://www.php.net/)

Pop Framework is a lightweight, modern PHP framework designed around **Vertical Slice Architecture (VSA)** principles. With zero external dependencies (except PHPUnit for testing), it provides everything you need to build modern PHP applications with React, Vue, or Svelte frontends through a custom Inertia.js adapter.

## ✨ Key Features

- **🎯 Vertical Slice Architecture** - Features are self-contained vertical slices, not horizontal layers
- **🚀 Zero Dependencies** - No external packages required (framework core)
- **⚡ Auto-Discovery** - Routes, middleware, and helpers automatically discovered
- **🔐 Built-in Security** - CSRF protection, RBAC permission system, session management
- **🗄️ Multi-Database Support** - SQLite, PostgreSQL, MySQL, SQL Server with auto-discovery
- **🎨 Modern Frontend** - Custom Inertia.js adapter for React/Vue/Svelte integration
- **🔧 Blade Templates** - Custom Blade engine implementation
- **🛣️ Powerful Routing** - Named routes, middleware, auto-discovery from Features
- **📦 Migration System** - SQL-based migrations with auto-run capability
- **🎭 Permission System** - RBAC with role hierarchy and inheritance

## 📋 Requirements

- **PHP 8.4+** with PDO extensions
- **Node.js 18+** and npm (for frontend)
- **Composer** for PHP dependencies

## 🚀 Quick Start

### Installation

Create a new project using Composer:

```bash
composer create-project pop-framework/pop my-app
cd my-app
```

### Configuration

1. Copy the environment file:
```bash
cp .env.example .env
```

2. Configure your database in `.env`:
```env
# Default database connection
DB_DEFAULT=main

# PostgreSQL Example
MAIN_DB_DRIVER=pgsql
MAIN_DB_HOST=localhost
MAIN_DB_PORT=5432
MAIN_DB_DATABASE=pop
MAIN_DB_USERNAME=postgres
MAIN_DB_PASSWORD=secret

# Or SQLite Example
APP_DB=Infrastructure/Persistence/Database/app.db
```

3. Install frontend dependencies:
```bash
npm install
```

### Running the Application

**Development Mode:**

```bash
# Terminal 1: Start PHP server
php -S localhost:8000 -t Infrastructure/Http/Public

# Terminal 2: Start Vite dev server
npm run dev
```

**Production Build:**

```bash
npm run build
```

Visit `http://localhost:8000` in your browser.

## 🏗️ Architecture

### Vertical Slice Architecture

Each feature is a self-contained vertical slice with all its logic in one directory:

```
Features/
└── YourFeature/
    ├── Action/
    │   ├── ActionCommand.php      # Input DTO
    │   ├── ActionHandler.php      # Business logic
    │   ├── ActionController.php   # HTTP layer
    │   └── ActionResponse.php     # Output DTO
    ├── Routes/
    │   └── feature.php             # Auto-discovered routes
    ├── Middleware/
    │   └── CustomMiddleware.php    # Auto-discovered middleware
    └── Shared/
        ├── Domain/                 # Domain models
        ├── Ports/                  # Interfaces
        └── Adapters/               # Implementations
```

### Directory Structure

```
Pop/
├── Framework/              # Core framework classes
│   ├── Http/              # Router, Request, Response, Controller
│   ├── Database/          # DB connections, migrations
│   ├── Security/          # CSRF, Permissions, Cookies
│   ├── View/              # Blade, Inertia
│   ├── Helpers/           # 15 global helper functions
│   └── Bootstrap.php      # Framework initialization
├── Features/              # Your application features (VSA slices)
│   └── Auth/              # Example authentication feature
├── Infrastructure/        # Infrastructure layer
│   ├── Http/
│   │   ├── Public/        # Web root (index.php)
│   │   ├── Controllers/   # Global controllers
│   │   ├── Routes/        # Global routes (web.php, api.php)
│   │   └── Middleware/    # Global middleware
│   ├── Persistence/
│   │   ├── migrations/    # SQL migration files
│   │   └── database/      # SQLite database
│   └── Resources/
│       └── js/            # React/Vue/Svelte frontend
├── Config/                # Configuration files
└── docs/                  # Documentation
```

## 📖 Core Concepts

### Routes

Routes are automatically discovered from:
- `Infrastructure/Http/Routes/*.php` - Global routes
- `Features/*/Routes/*.php` - Feature-specific routes

**Example route definition:**

```php
use Framework\Http\Router;

Router::get('/users', 'UserController@index', ['auth'])->name('users.index');
Router::post('/users', 'UserController@store', ['auth', 'permission:users.create']);
```

### Controllers

Extend the base controller for convenient request/response handling:

```php
use Framework\Http\Controller;

class UserController extends Controller
{
    public function index(): void
    {
        $search = $this->input('search');
        $users = /* fetch users */;

        $this->inertia('Users/Index', ['users' => $users]);
    }

    public function store(): void
    {
        $missing = $this->validate(['name', 'email']);
        if (!empty($missing)) {
            $this->validationError(['missing' => $missing]);
        }

        $data = $this->only(['name', 'email']);
        // Create user...
        $this->created($user, 'User created successfully');
    }
}
```

### Middleware

Create middleware in `Infrastructure/Http/Middleware/` or `Features/*/Middleware/`:

```php
namespace Infrastructure\Http\Middleware;

class AuthMiddleware
{
    public function handle()
    {
        if (!session('authenticated')) {
            redirect('auth.login');
            return false; // Halt request
        }
        return true; // Continue
    }
}
```

Use in routes: `['auth', 'permission:users.view']`

### Helper Functions

15 global helper functions available everywhere:

```php
env('APP_ENV')                    // Environment variables
config('database.default')        // Configuration values
session('user')                   // Session data
request('email')                  // Request input
view('welcome', $data)            // Render Blade template
inertia('Dashboard', $props)      // Render Inertia component
route('users.show', ['id' => 1])  // Generate route URL
can('users.create')               // Check permissions
redirect('dashboard')             // Redirect to named route
csrf_token()                      // Get CSRF token
```

### Database

Multi-database support with environment-based auto-discovery:

```php
// Configure in .env:
// MAIN_DB_HOST=localhost
// ANALYTICS_DB_HOST=analytics.example.com

$db = DB::connection('main');
$stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();
```

**Migrations** are auto-run on bootstrap from `Infrastructure/Persistence/migrations/`:

```
migrations/
├── 001_create_users_table.sql
├── 002_add_permissions.sql
└── 003_create_sessions.sql
```

### Permissions

Built-in RBAC with role hierarchy:

```php
// Check permissions
if (can('users.create')) {
    // User has permission
}

// In routes
Router::post('/admin/settings', 'SettingsController@update', [
    'auth',
    'permission:system.admin'
]);

// Role hierarchy (defined in Config/permissions.php)
// Superadmin → Corridor → Manager → Officer
```

### Inertia.js Integration

Custom Inertia adapter (no external PHP dependency):

```php
// In controller
$this->inertia('Users/Index', [
    'users' => $users,
    'filters' => $filters
]);
```

```jsx
// In React (resources/js/Pages/Users/Index.jsx)
import { Head } from '@inertiajs/react';

export default function Index({ users, filters }) {
    return (
        <>
            <Head title="Users" />
            <div className="users-page">
                {/* Your component */}
            </div>
        </>
    );
}
```

## 📚 Documentation

- [Installation Guide](docs/INSTALLATION.md)
- [Architecture Overview](CLAUDE.md)
- [Authentication System](docs/auth/README.md)
- [Contributing Guidelines](CONTRIBUTING.md)
- [Changelog](CHANGELOG.md)

## 🧪 Testing

Run the test suite:

```bash
composer test
# or
./vendor/bin/phpunit
```

## 🤝 Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## 📄 License

The Pop Framework is open-sourced software licensed under the [MIT license](LICENSE).

## 🙏 Credits

Built with modern PHP 8.4+ and inspired by Vertical Slice Architecture principles.

## 📞 Support

- **Issues:** [GitHub Issues](https://github.com/YOUR_USERNAME/pop/issues)
- **Documentation:** [GitHub Repository](https://github.com/YOUR_USERNAME/pop)

---

**Note:** Before publishing to production, make sure to:
- Update `YOUR_USERNAME` and `YOUR_NAME` in `composer.json`
- Update GitHub URLs in this README
- Configure your production environment variables
- Run the test suite
- Review security settings
