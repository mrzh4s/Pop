# Middleware Directory

This directory contains global middleware classes that are automatically discovered and registered by the Router.

## Auto-Discovery

All middleware classes in this directory are automatically loaded when the application boots. No manual registration required!

## Naming Convention

- **File name**: `{Name}Middleware.php`
- **Class name**: `{Name}Middleware`
- **Route usage**: Auto-derived (e.g., `AuthMiddleware` → `auth`)

## Examples

### AuthMiddleware.php
```php
<?php
namespace Infrastructure\Http\Middleware;

class AuthMiddleware
{
    public function handle()
    {
        if (!session('authenticated')) {
            redirect('auth.signin');
            return false;
        }
        return true;
    }
}
```

**Usage in routes:**
```php
Router::get('/dashboard', 'DashboardPage@show', ['auth']);
```

### Parameterized Middleware

Middleware can accept parameters using colon (`:`) syntax:

**File**: `PermissionMiddleware.php`

```php
<?php
namespace Infrastructure\Http\Middleware;

class PermissionMiddleware
{
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

**Usage in routes:**
```php
// Format: 'middleware:param1:param2:param3'
Router::get('/posts', 'PostPage@index', ['permission:posts.view']);
Router::post('/posts', 'PostPage@create', ['permission:posts.create']);
Router::get('/admin/settings', 'SettingsPage@index', ['role:admin']);
```

### Custom Middleware with PascalCase

**File**: `RateLimitMiddleware.php`

```php
<?php
namespace Infrastructure\Http\Middleware;

class RateLimitMiddleware
{
    public function handle()
    {
        // Rate limiting logic
        return true;
    }
}
```

**Usage in routes:**
```php
// Auto-derived name: rate-limit
Router::post('/api/data', 'DataController@store', ['rate-limit']);
```

## Middleware Contract

Your middleware class must:
1. Have a `handle()` method OR be invokable (`__invoke`)
2. Return `false` to halt the request
3. Return `true` or `void` to continue
4. Can redirect or exit directly

## Feature-Specific Middleware

For feature-specific middleware, create them in:
```
Features/{FeatureName}/Middleware/{Name}Middleware.php
```

They will be auto-discovered and registered the same way!
