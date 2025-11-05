# Database Configuration Guide

## Overview

Pop Framework now supports **dynamic database connections** with auto-discovery from `config/Database.php`. You can define unlimited database connections with any name you want, not just the hardcoded ones.

## Quick Start

### 1. Define Connections in `config/Database.php`

```php
return [
    'default' => 'main',  // Default connection when none specified

    'connections' => [
        'main' => [...],
        'analytics' => [...],
        'cache' => [...],
        'reporting' => [...],
        // Add as many as you need!
    ]
];
```

### 2. Use in Your Code

```php
// Get default connection (defined in config)
$db = DB::connection();

// Get specific connection
$mainDb = DB::connection('main');
$analyticsDb = DB::connection('analytics');
$cacheDb = DB::connection('cache');

// Execute queries
$stmt = DB::query("SELECT * FROM users", [], 'main');
$stmt = DB::query("SELECT * FROM logs", [], 'analytics');
```

## Supported Database Drivers

### SQLite

```php
'cache' => [
    'driver' => 'sqlite',
    'database' => 'database/cache.db',
    'foreign_keys' => true,
    'options' => [
        'journal_mode' => 'WAL',
        'synchronous' => 'NORMAL',
        'cache_size' => 10000,
    ]
]
```

### PostgreSQL

```php
'analytics' => [
    'driver' => 'pgsql',
    'host' => env('ANALYTICS_DB_HOST', 'localhost'),
    'port' => env('ANALYTICS_DB_PORT', 5432),
    'database' => env('ANALYTICS_DB_DATABASE', 'analytics'),
    'username' => env('ANALYTICS_DB_USERNAME', 'postgres'),
    'password' => env('ANALYTICS_DB_PASSWORD', ''),
    'charset' => 'utf8',
    'schema' => 'public',
    'options' => [
        'statement_timeout' => '300s',
        'timezone' => 'UTC',
    ]
]
```

### MySQL

```php
'mysql_main' => [
    'driver' => 'mysql',
    'host' => env('MYSQL_HOST', 'localhost'),
    'port' => env('MYSQL_PORT', 3306),
    'database' => env('MYSQL_DATABASE', 'app_db'),
    'username' => env('MYSQL_USERNAME', 'root'),
    'password' => env('MYSQL_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'strict' => true,
    'timezone' => '+00:00',
]
```

### SQL Server

```php
'sqlserver' => [
    'driver' => 'sqlsrv',
    'host' => env('SQLSERVER_HOST', 'localhost'),
    'port' => env('SQLSERVER_PORT', 1433),
    'database' => env('SQLSERVER_DATABASE', 'master'),
    'username' => env('SQLSERVER_USERNAME', 'sa'),
    'password' => env('SQLSERVER_PASSWORD', ''),
]
```

## Usage Examples

### Basic Connection

```php
// Get default connection
$db = DB::connection();

// Get named connection
$analyticsDb = DB::connection('analytics');
$cacheDb = DB::connection('cache');
```

### Execute Queries

```php
// Query on default connection
$users = DB::query("SELECT * FROM users WHERE active = ?", [1]);

// Query on specific connection
$logs = DB::query("SELECT * FROM logs WHERE date > ?", ['2024-01-01'], 'analytics');

// Fetch results
foreach ($users->fetchAll() as $user) {
    echo $user->name;
}
```

### Multiple Connections Example

```php
// Read from source database
$sourceDb = DB::connection('source');
$stmt = $sourceDb->prepare("SELECT * FROM old_users");
$stmt->execute();
$users = $stmt->fetchAll();

// Write to destination database
$destDb = DB::connection('dest');
foreach ($users as $user) {
    $stmt = $destDb->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
    $stmt->execute([$user->name, $user->email]);
}
```

### Health Checks

```php
// Test all connections
$health = DB::health();

foreach ($health as $name => $status) {
    echo "$name: " . $status['status'] . "\n";
    if ($status['status'] === 'failed') {
        echo "Error: " . $status['error'] . "\n";
    }
}
```

### List Available Connections

```php
$connections = DB::connections();
// Returns: ['main', 'source', 'dest', 'analytics', 'cache', ...]

echo "Available connections: " . implode(', ', $connections);
```

### Get Connection Stats

```php
// Stats for specific connection
$stats = DB::stats('main');

// Stats for all connections
$allStats = DB::stats();
```

### Reset Connections

```php
// Reset specific connection (force reconnect)
DB::reset('analytics');

// Reset all connections
DB::resetAll();
```

## Adding New Connections

### Step 1: Add to `config/Database.php`

```php
'reporting' => [
    'driver' => 'mysql',
    'host' => env('REPORTING_DB_HOST', 'localhost'),
    'database' => env('REPORTING_DB_DATABASE', 'reports'),
    'username' => env('REPORTING_DB_USERNAME', 'root'),
    'password' => env('REPORTING_DB_PASSWORD', ''),
]
```

### Step 2: Add Environment Variables (`.env`)

```env
REPORTING_DB_HOST=reporting.example.com
REPORTING_DB_DATABASE=reports_prod
REPORTING_DB_USERNAME=reporting_user
REPORTING_DB_PASSWORD=secret123
```

### Step 3: Use It!

```php
$reportingDb = DB::connection('reporting');
$reports = DB::query("SELECT * FROM monthly_reports", [], 'reporting');
```

## Advanced Features

### Connection Pooling

All connections are automatically pooled and reused. Health checks ensure connections are alive before use.

```php
// First call - creates connection
$db1 = DB::connection('main');

// Second call - reuses existing connection
$db2 = DB::connection('main');

// Same connection object
var_dump($db1 === $db2); // true
```

### Retry Logic (PostgreSQL)

PostgreSQL connections automatically retry with exponential backoff:

```php
'pool' => [
    'max_retries' => 3,
    'base_timeout' => 10,
    'ping_timeout' => 1000,
]
```

### Custom PDO Options

```php
'connections' => [
    'custom' => [
        'driver' => 'mysql',
        // ... other config ...
        'pdo_options' => [
            PDO::ATTR_TIMEOUT => 30,
            PDO::ATTR_PERSISTENT => true,
        ]
    ]
]
```

## Migration from Old System

The new system is **100% backward compatible**:

```php
// Old way - still works!
DB::connection('main');
DB::connection('source');
DB::connection('dest');

// New way - unlimited connections!
DB::connection('analytics');
DB::connection('cache');
DB::connection('reporting');
DB::connection('anything_you_want');
```

## Troubleshooting

### Connection Not Found Error

```
Connection 'analytics' not found. Available connections: main, source, dest
```

**Solution**: Add the connection to `config/Database.php`

### Driver Not Supported Error

```
Unsupported database driver: mongodb
```

**Solution**: Use supported drivers: `sqlite`, `pgsql`, `mysql`, `sqlsrv`

### Connection Failed

Check:
1. Database credentials in `.env`
2. Database server is running
3. Network connectivity
4. Firewall rules

Use health check to diagnose:

```php
$health = DB::health();
print_r($health);
```

## Best Practices

1. **Use environment variables** for credentials
2. **Set a default connection** in config
3. **Use meaningful names** for connections
4. **Test connections** with `DB::health()` in setup
5. **Log slow connections** (automatic in debug mode)
6. **Reset connections** if experiencing issues

## Performance Tips

### SQLite Optimization

```php
'options' => [
    'journal_mode' => 'WAL',      // Better concurrency
    'synchronous' => 'NORMAL',    // Faster writes
    'cache_size' => 10000,        // More memory cache
    'temp_store' => 'MEMORY',     // Temp tables in RAM
]
```

### PostgreSQL Optimization

```php
'options' => [
    'statement_timeout' => '300s',  // Prevent long queries
    'lock_timeout' => '30s',        // Prevent deadlocks
    'timezone' => 'UTC',            // Consistent timezone
]
```

### MySQL Optimization

```php
'strict' => true,                  // Enforce data integrity
'charset' => 'utf8mb4',            // Full Unicode support
'collation' => 'utf8mb4_unicode_ci',
```

## Complete Example: Multi-Database Application

```php
// config/Database.php
return [
    'default' => 'main',

    'connections' => [
        'main' => [
            'driver' => 'sqlite',
            'database' => 'database/app.db',
        ],

        'analytics' => [
            'driver' => 'pgsql',
            'host' => env('ANALYTICS_DB_HOST'),
            'database' => env('ANALYTICS_DB_DATABASE'),
            'username' => env('ANALYTICS_DB_USERNAME'),
            'password' => env('ANALYTICS_DB_PASSWORD'),
        ],

        'cache' => [
            'driver' => 'sqlite',
            'database' => 'database/cache.db',
        ],
    ]
];

// Usage in your application
class UserService {
    public function getUser($id) {
        $stmt = DB::query("SELECT * FROM users WHERE id = ?", [$id], 'main');
        return $stmt->fetch();
    }

    public function logActivity($userId, $action) {
        DB::query(
            "INSERT INTO activity_log (user_id, action, timestamp) VALUES (?, ?, NOW())",
            [$userId, $action],
            'analytics'
        );
    }

    public function cacheUserData($userId, $data) {
        DB::query(
            "INSERT OR REPLACE INTO user_cache (user_id, data) VALUES (?, ?)",
            [$userId, json_encode($data)],
            'cache'
        );
    }
}
```