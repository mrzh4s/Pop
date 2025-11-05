# Database-Based Permissions Guide

## Overview

Yes! Pop Framework **fully supports storing permissions in database tables**. This gives you:
- ✅ **Runtime management** - Add/edit/delete permissions via admin UI
- ✅ **No file edits** - Manage everything through database
- ✅ **User management** - Assign roles to users dynamically
- ✅ **Audit trails** - Track who changed what and when
- ✅ **Multi-tenant ready** - Perfect for SaaS applications

## Three Permission Sources

The system supports three modes:

1. **`config`** - Load from `config/Permissions.php` (file-based)
2. **`database`** - Load from database tables
3. **`both`** - Merge config + database (config takes precedence)

## Quick Start

### Step 1: Run Database Migration

```bash
# Run the SQL migration to create tables
sqlite3 apps/database/app.db < apps/database/migrations/001_create_permissions_tables.sql
```

Or use your preferred database tool to execute the SQL file.

### Step 2: Set Permission Source to Database

```php
// In your bootstrap or admin panel
$perm = Permission::getInstance();
$perm->setSource('database');  // Now loads from database!
```

Or update directly in database:

```sql
UPDATE permission_settings
SET setting_value = 'database'
WHERE setting_key = 'source';
```

### Step 3: Start Using!

```php
// Check permission (now loaded from database)
if (can('users.create')) {
    // User can create users
}
```

## Database Schema

### Core Tables

**`roles`** - Store role definitions
```sql
CREATE TABLE roles (
    id INTEGER PRIMARY KEY,
    name VARCHAR(50) UNIQUE,      -- 'admin', 'manager', 'user'
    display_name VARCHAR(100),     -- 'Administrator'
    description TEXT,
    is_system BOOLEAN DEFAULT 0
);
```

**`permissions`** - Store permission definitions
```sql
CREATE TABLE permissions (
    id INTEGER PRIMARY KEY,
    name VARCHAR(100) UNIQUE,      -- 'users.create'
    display_name VARCHAR(100),     -- 'Create Users'
    description TEXT,
    module VARCHAR(50),            -- 'users', 'projects'
    is_public BOOLEAN DEFAULT 0    -- If true, all users have this
);
```

**`role_hierarchies`** - Role inheritance
```sql
CREATE TABLE role_hierarchies (
    parent_role_id INTEGER,        -- Role that inherits
    child_role_id INTEGER          -- Role to inherit from
);
```

**`role_permissions`** - Which roles have which permissions
```sql
CREATE TABLE role_permissions (
    role_id INTEGER,
    permission_id INTEGER
);
```

**`user_roles`** - Assign roles to users
```sql
CREATE TABLE user_roles (
    user_id INTEGER,
    role_id INTEGER,
    expires_at DATETIME            -- Optional expiration
);
```

## Managing Permissions via Database

###  1. Create Roles

```php
$perm = Permission::getInstance();

// Create new role
$roleId = $perm->createRole(
    'editor',                      // name
    'Editor',                      // display name
    'Can edit content'             // description
);
```

Or via SQL:

```sql
INSERT INTO roles (name, display_name, description)
VALUES ('editor', 'Editor', 'Can edit content');
```

### 2. Create Permissions

```php
$perm = Permission::getInstance();

// Create new permission
$permId = $perm->createPermission(
    'articles.publish',            // name
    'Publish Articles',            // display name
    'Can publish articles to live site',  // description
    'articles',                    // module
    false                          // is_public
);
```

Or via SQL:

```sql
INSERT INTO permissions (name, display_name, description, module, is_public)
VALUES ('articles.publish', 'Publish Articles', 'Can publish articles', 'articles', 0);
```

### 3. Grant Permissions to Roles

```php
$perm = Permission::getInstance();

// Grant permission to role
$perm->grantPermission('editor', 'articles.publish');

// Grant multiple permissions
$perm->grantPermission('editor', 'articles.create');
$perm->grantPermission('editor', 'articles.edit');
```

Or via SQL:

```sql
INSERT INTO role_permissions (role_id, permission_id)
SELECT
    (SELECT id FROM roles WHERE name = 'editor'),
    (SELECT id FROM permissions WHERE name = 'articles.publish');
```

### 4. Assign Roles to Users

```php
$perm = Permission::getInstance();

// Assign role to user
$userId = 123;
$perm->assignRole($userId, 'editor', $assignedBy = 1);

// Remove role from user
$perm->removeRole($userId, 'editor');
```

Or via SQL:

```sql
-- Assign role
INSERT INTO user_roles (user_id, role_id)
VALUES (123, (SELECT id FROM roles WHERE name = 'editor'));

-- Remove role
DELETE FROM user_roles
WHERE user_id = 123
AND role_id = (SELECT id FROM roles WHERE name = 'editor');
```

### 5. Revoke Permissions

```php
$perm = Permission::getInstance();

// Revoke permission from role
$perm->revokePermission('editor', 'articles.publish');
```

Or via SQL:

```sql
DELETE FROM role_permissions
WHERE role_id = (SELECT id FROM roles WHERE name = 'editor')
AND permission_id = (SELECT id FROM permissions WHERE name = 'articles.publish');
```

## Complete Example: Blog System

### Step 1: Create Roles

```php
$perm = Permission::getInstance();

// Create roles
$perm->createRole('admin', 'Administrator', 'Full access');
$perm->createRole('editor', 'Editor', 'Can edit and publish content');
$perm->createRole('author', 'Author', 'Can write articles');
$perm->createRole('subscriber', 'Subscriber', 'Can read articles');
```

### Step 2: Create Permissions

```php
// Article permissions
$perm->createPermission('articles.view', 'View Articles', null, 'articles', true);  // Public
$perm->createPermission('articles.create', 'Create Articles', null, 'articles');
$perm->createPermission('articles.edit.own', 'Edit Own Articles', null, 'articles');
$perm->createPermission('articles.edit.any', 'Edit Any Article', null, 'articles');
$perm->createPermission('articles.delete', 'Delete Articles', null, 'articles');
$perm->createPermission('articles.publish', 'Publish Articles', null, 'articles');

// Comment permissions
$perm->createPermission('comments.create', 'Create Comments', null, 'comments', true);
$perm->createPermission('comments.moderate', 'Moderate Comments', null, 'comments');
```

### Step 3: Grant Permissions to Roles

```php
// Admin - all permissions
$perm->grantPermission('admin', 'articles.create');
$perm->grantPermission('admin', 'articles.edit.any');
$perm->grantPermission('admin', 'articles.delete');
$perm->grantPermission('admin', 'articles.publish');
$perm->grantPermission('admin', 'comments.moderate');

// Editor - publish and edit
$perm->grantPermission('editor', 'articles.create');
$perm->grantPermission('editor', 'articles.edit.any');
$perm->grantPermission('editor', 'articles.publish');
$perm->grantPermission('editor', 'comments.moderate');

// Author - write only
$perm->grantPermission('author', 'articles.create');
$perm->grantPermission('author', 'articles.edit.own');

// Subscriber gets nothing (can view via is_public)
```

### Step 4: Assign Roles to Users

```php
// Make user #1 an admin
$perm->assignRole(1, 'admin');

// Make user #5 an editor
$perm->assignRole(5, 'editor');

// Make users #10-15 authors
for ($i = 10; $i <= 15; $i++) {
    $perm->assignRole($i, 'author');
}
```

### Step 5: Use in Application

```php
// In ArticleController
class ArticleController {
    public function publish($articleId) {
        // Check permission
        if (!can('articles.publish')) {
            redirect('error.403');
            return;
        }

        $article = Article::find($articleId);
        $article->status = 'published';
        $article->save();

        redirect('articles.index');
    }

    public function edit($articleId) {
        $article = Article::find($articleId);

        // Can edit any article?
        if (can('articles.edit.any')) {
            return $this->showEditForm($article);
        }

        // Can edit own article?
        if (can('articles.edit.own', null, 'own', $article->author_id)) {
            return $this->showEditForm($article);
        }

        // No permission
        redirect('error.403');
    }
}
```

## Admin UI Examples

### Role Management Page

```php
// Get all roles
$db = DB::connection();
$roles = $db->query("SELECT * FROM roles ORDER BY name")->fetchAll();

// Display
foreach ($roles as $role) {
    echo "<div class='role'>";
    echo "<h3>{$role->display_name}</h3>";
    echo "<p>{$role->description}</p>";
    echo "<a href='/admin/roles/{$role->id}/edit'>Edit</a>";
    echo "</div>";
}
```

### Permission Management Page

```php
// Get all permissions by module
$db = DB::connection();
$permissions = $db->query("
    SELECT module, name, display_name
    FROM permissions
    ORDER BY module, name
")->fetchAll();

// Group by module
$byModule = [];
foreach ($permissions as $perm) {
    $byModule[$perm->module][] = $perm;
}

// Display
foreach ($byModule as $module => $perms) {
    echo "<h2>{$module}</h2>";
    foreach ($perms as $perm) {
        echo "<div>{$perm->display_name}</div>";
    }
}
```

### User Role Assignment

```php
// Assign role to user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'];
    $roleName = $_POST['role'];

    try {
        $perm = Permission::getInstance();
        $perm->assignRole($userId, $roleName, session('user.id'));

        echo "Role assigned successfully!";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Get available roles for dropdown
$db = DB::connection();
$roles = $db->query("SELECT name, display_name FROM roles")->fetchAll();

echo "<form method='POST'>";
echo "<select name='role'>";
foreach ($roles as $role) {
    echo "<option value='{$role->name}'>{$role->display_name}</option>";
}
echo "</select>";
echo "<button>Assign Role</button>";
echo "</form>";
```

## Advanced Features

### Temporary Role Assignments

```sql
-- Assign role that expires in 30 days
INSERT INTO user_roles (user_id, role_id, expires_at)
VALUES (
    123,
    (SELECT id FROM roles WHERE name = 'editor'),
    DATETIME('now', '+30 days')
);
```

### Get User's Permissions

```php
// Get all permissions for a user
$userId = 123;
$db = DB::connection();

$permissions = $db->query("
    SELECT DISTINCT p.name
    FROM user_roles ur
    JOIN roles r ON ur.role_id = r.id
    JOIN role_permissions rp ON rp.role_id = r.id
    JOIN permissions p ON rp.permission_id = p.id
    WHERE ur.user_id = ?
    AND (ur.expires_at IS NULL OR ur.expires_at > CURRENT_TIMESTAMP)
", [$userId])->fetchAll();

foreach ($permissions as $perm) {
    echo $perm->name . "\n";
}
```

### Permission Audit Log

```sql
-- Log permission changes
INSERT INTO permission_audit_log (action, entity_type, entity_id, user_id, details)
VALUES ('grant', 'role_permission', 123, 1, '{"role": "editor", "permission": "articles.publish"}');

-- View audit log
SELECT * FROM permission_audit_log
WHERE entity_type = 'role_permission'
ORDER BY created_at DESC
LIMIT 100;
```

## Switching Between Sources

### Use Config Only

```php
$perm = Permission::getInstance();
$perm->setSource('config');
// Now loads from config/Permissions.php
```

### Use Database Only

```php
$perm = Permission::getInstance();
$perm->setSource('database');
// Now loads from database tables
```

### Use Both (Merge)

```php
$perm = Permission::getInstance();
$perm->setSource('both');
// Loads from config first, then merges database
// Config permissions take precedence
```

## Migration from Config to Database

If you have permissions in `config/Permissions.php` and want to move them to database:

```php
// Migration script
$perm = Permission::getInstance();
$db = DB::connection();

// Load current config
$config = require ROOT_PATH . '/config/Permissions.php';

// Insert roles
foreach ($config['roles'] as $roleName => $children) {
    try {
        $perm->createRole($roleName, ucfirst($roleName), "");
    } catch (Exception $e) {
        // Already exists
    }
}

// Insert role hierarchies
foreach ($config['roles'] as $parentRole => $childRoles) {
    foreach ($childRoles as $childRole) {
        $db->query("
            INSERT OR IGNORE INTO role_hierarchies (parent_role_id, child_role_id)
            SELECT
                (SELECT id FROM roles WHERE name = '{$parentRole}'),
                (SELECT id FROM roles WHERE name = '{$childRole}')
        ");
    }
}

// Insert permissions
foreach ($config['permissions'] as $permName => $allowedRoles) {
    $isPublic = ($allowedRoles === '*');

    // Create permission
    try {
        $perm->createPermission($permName, ucfirst($permName), "", "", $isPublic);
    } catch (Exception $e) {
        // Already exists
    }

    // Grant to roles
    if (is_array($allowedRoles)) {
        foreach ($allowedRoles as $roleName) {
            try {
                $perm->grantPermission($roleName, $permName);
            } catch (Exception $e) {
                // Already granted
            }
        }
    }
}

// Switch to database source
$perm->setSource('database');

echo "Migration complete!";
```

## Best Practices

1. **Use database for production** - Easier to manage and audit
2. **Use config for development** - Faster, no database setup needed
3. **Cache permissions** - Load once per request, not per check
4. **Audit all changes** - Use `permission_audit_log` table
5. **Backup regularly** - Export permissions before major changes
6. **Test after changes** - Always test permission changes before deploying
7. **Use transactions** - When making multiple permission changes
8. **Document custom permissions** - Keep a list of what each permission does

## Performance Considerations

### Caching

The system will support caching in the future. For now:

```php
// Load permissions once at app startup
$perm = Permission::getInstance();
// All subsequent can() calls use cached data
```

### Database Indexes

The migration includes indexes for fast lookups:
- `roles.name`
- `permissions.name`
- `role_permissions(role_id, permission_id)`
- `user_roles(user_id, role_id)`

### Minimize Permission Checks

```php
// Bad - checks permission multiple times
foreach ($items as $item) {
    if (can('items.edit')) {
        editItem($item);
    }
}

// Good - check once
$canEdit = can('items.edit');
foreach ($items as $item) {
    if ($canEdit) {
        editItem($item);
    }
}
```

## Troubleshooting

### Permissions not updating?

```php
// Force reload from database
$perm = Permission::getInstance();
$perm->reload();
```

### Check current source

```php
$perm = Permission::getInstance();
echo "Permission source: " . $perm->getSource();
```

### Debug permissions

```php
$debug = Permission::getInstance()->getDebugInfo();
print_r($debug);
/*
Array (
    [permission_source] => database
    [total_permissions] => 25
    [total_roles] => 5
    ...
)
*/
```

## Summary

**Before:** Permissions hardcoded in PHP files
**Now:** Permissions stored in database tables!

You can:
- ✅ Create roles and permissions via admin UI
- ✅ Assign roles to users dynamically
- ✅ Grant/revoke permissions in real-time
- ✅ Audit who changed what
- ✅ Import/export permissions
- ✅ Switch between config/database/both modes

The same `can()` function works whether you use config or database - your application code doesn't need to change!
