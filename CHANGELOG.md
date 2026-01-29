# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2025-XX-XX

### Added

#### Framework Core
- Initial release of Pop Framework
- Vertical Slice Architecture (VSA) implementation
- Zero external dependencies design (except PHPUnit for testing)
- PHP 8.4+ requirement with strict typing

#### HTTP Layer
- Custom Router with auto-discovery from `Infrastructure/Http/Routes/` and `Features/*/Routes/`
- Named routes with parameter support
- Middleware system with auto-discovery
- Parameterized middleware support (e.g., `permission:users.view`)
- Base Controller class with request/response helpers
- Request parsing with JSON and form-data auto-detection
- Response helpers for JSON, Inertia, Blade, and redirects
- Session management with device tracking and IP validation
- CSRF protection for state-changing requests
- Cookie handling with security features

#### Database
- Multi-database connection factory (DB::connection())
- Support for SQLite, PostgreSQL, MySQL, and SQL Server
- Environment-based auto-discovery of database connections
- SQL-based migration system with auto-run capability
- Migration files loaded from `Infrastructure/Persistence/migrations/`

#### Security
- Role-Based Access Control (RBAC) permission system
- Role hierarchy with inheritance (Superadmin → Corridor → Manager → Officer)
- Permission checking with dot notation (`can('users.create')`)
- CSRF token generation and validation
- Secure session handling
- Login attempt throttling
- Password hashing with bcrypt

#### View Layer
- Custom Blade template engine implementation
- Custom Inertia.js PHP adapter (no external dependency)
- SSR support preparation
- Asset versioning
- Lazy prop loading support
- Blade template rendering with `view()` helper
- Inertia component rendering with `inertia()` helper

#### Helper Functions
15 auto-loaded global helper functions:
- `env($key, $default)` - Environment variable access
- `config($key, $default)` - Configuration value access
- `session($key, $default)` - Session data management
- `request($key, $default)` - Request input retrieval
- `view($template, $data)` - Blade template rendering
- `inertia($component, $props)` - Inertia component rendering
- `route($name, $params)` - Named route URL generation
- `can($permission)` - Permission checking
- `redirect($routeName)` - Named route redirection
- `csrf_token()` - CSRF token retrieval
- `csrf_field()` - CSRF hidden input field
- `cookie()` - Cookie management
- `activity()` - Activity logging
- `traffic()` - Traffic logging
- `debug()` - Debug helpers

#### Configuration
- Environment-based configuration with `.env` file support
- Auto-discovery of configuration from environment variables
- Multi-database configuration via env pattern (`{NAME}_DB_HOST`)
- Permission configuration with role hierarchy
- FTP configuration support

#### Logging & Monitoring
- Activity logging system
- Traffic logging with query builder
- Login attempt tracking
- Session activity tracking

#### Frontend Integration
- Vite build system integration
- React starter setup with Inertia.js
- Tailwind CSS v4 configuration
- Hot module replacement (HMR) support
- Asset bundling and versioning
- Example authentication UI

#### Example Features
- Authentication feature slice (Features/Auth/)
  - Login with Command/Handler/Controller pattern
  - User domain models
  - Repository pattern implementation (Ports & Adapters)
  - Custom exceptions
  - Feature-specific routes
  - Admin middleware example

#### Developer Experience
- Auto-discovery for routes, middleware, and controllers
- PSR-4 autoloading
- Composer scripts for dev server and builds
- Example `.env` file
- Development server script (`run.sh`)
- Graceful error handling with debug mode

### Documentation
- Comprehensive CLAUDE.md with architecture guide
- Authentication system documentation
- SQL schema documentation
- Permission system guide
- README with quick start guide
- CHANGELOG for version tracking
- CONTRIBUTING guidelines

### Infrastructure
- SQLite database setup
- PostgreSQL schema examples
- SQL migration files for auth and permissions
- Default user seeding
- Frontend build configuration
- Git configuration (.gitignore)

---

## Future Releases

### Planned for 1.1.0
- CLI command system implementation
- Code generation commands (make:feature, make:controller, etc.)
- Migration management commands
- Route listing command

### Planned for 1.2.0
- Cache abstraction layer
- File storage abstraction
- Queue system

### Planned for 2.0.0
- Package split (framework core vs starter kit)
- Enhanced logging with PSR-3 compliance
- Email system integration
- Additional database drivers

---

## Release Notes

### Version 1.0.0

This is the initial stable release of Pop Framework. The framework provides a complete foundation for building modern PHP applications with:

- **Zero Dependencies**: No external packages required for the core framework
- **Modern Architecture**: Vertical Slice Architecture for better feature organization
- **Full-Stack**: Backend framework + Inertia.js for seamless frontend integration
- **Security First**: Built-in CSRF, RBAC, and session security
- **Developer Friendly**: Auto-discovery reduces boilerplate significantly

**Minimum Requirements:**
- PHP 8.4+
- PDO extensions (pdo_sqlite, pdo_pgsql, pdo_mysql as needed)
- Node.js 18+ and npm (for frontend)
- Composer 2.x

**Breaking Changes:**
- N/A (initial release)

**Migration Guide:**
- N/A (initial release)

---

[Unreleased]: https://github.com/YOUR_USERNAME/pop/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/YOUR_USERNAME/pop/releases/tag/v1.0.0
