-- ============================================================
-- Permission System Schema (SQLite)
-- File: apps/database/migrations/001_create_permissions_tables.sql
--
-- Uses ATTACH DATABASE to create auth schema in SQLite
-- For PostgreSQL with schemas, see: docs/permission/permission.table.sql
-- Depends on: 000_create_auth_tables.sql (for auth.users)
-- ============================================================

BEGIN TRANSACTION;

-- Attach auth database (should already be attached from migration 000)
ATTACH DATABASE 'database/auth.db' AS auth;

-- Drop tables if existing (to allow re-run)
DROP TABLE IF EXISTS auth.permission_audit_log;
DROP TABLE IF EXISTS auth.permission_attributes;
DROP TABLE IF EXISTS auth.permission_settings;
DROP TABLE IF EXISTS auth.user_permissions;
DROP TABLE IF EXISTS auth.role_permissions;
DROP TABLE IF EXISTS auth.role_hierarchies;
DROP TABLE IF EXISTS auth.permissions;
DROP TABLE IF EXISTS auth.roles;

-- ============== ROLES TABLE ==============
-- Core roles for RBAC system
CREATE TABLE IF NOT EXISTS auth.roles
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE,
    display_name VARCHAR(255) NOT NULL,
    description TEXT,
    level INTEGER DEFAULT 0,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_roles_name ON auth.roles(name);
CREATE INDEX IF NOT EXISTS idx_roles_level ON auth.roles(level);
CREATE INDEX IF NOT EXISTS idx_roles_is_active ON auth.roles(is_active);

-- ============== PERMISSIONS TABLE ==============
-- Individual permissions/capabilities
CREATE TABLE IF NOT EXISTS auth.permissions
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE,
    display_name VARCHAR(255) NOT NULL,
    description TEXT,
    module VARCHAR(100),
    category VARCHAR(100),
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_permissions_name ON auth.permissions(name);
CREATE INDEX IF NOT EXISTS idx_permissions_module ON auth.permissions(module);
CREATE INDEX IF NOT EXISTS idx_permissions_category ON auth.permissions(category);
CREATE INDEX IF NOT EXISTS idx_permissions_is_active ON auth.permissions(is_active);

-- ============== ROLE HIERARCHIES TABLE ==============
-- Defines parent-child relationships between roles
CREATE TABLE IF NOT EXISTS auth.role_hierarchies
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    parent_role_id INTEGER NOT NULL,
    child_role_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_role_id) REFERENCES auth.roles(id) ON DELETE CASCADE,
    FOREIGN KEY (child_role_id) REFERENCES auth.roles(id) ON DELETE CASCADE,
    UNIQUE (parent_role_id, child_role_id)
);

CREATE INDEX IF NOT EXISTS idx_role_hierarchies_parent ON auth.role_hierarchies(parent_role_id);
CREATE INDEX IF NOT EXISTS idx_role_hierarchies_child ON auth.role_hierarchies(child_role_id);

-- ============== ROLE PERMISSIONS TABLE ==============
-- Many-to-many relationship between roles and permissions
CREATE TABLE IF NOT EXISTS auth.role_permissions
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    role_id INTEGER NOT NULL,
    permission_id INTEGER NOT NULL,
    granted_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES auth.roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES auth.permissions(id) ON DELETE CASCADE,
    UNIQUE (role_id, permission_id)
);

CREATE INDEX IF NOT EXISTS idx_role_permissions_role ON auth.role_permissions(role_id);
CREATE INDEX IF NOT EXISTS idx_role_permissions_permission ON auth.role_permissions(permission_id);

-- ============== USER PERMISSIONS TABLE ==============
-- Direct permission assignments to users (overrides)
CREATE TABLE IF NOT EXISTS auth.user_permissions
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    permission_id INTEGER NOT NULL,
    granted INTEGER NOT NULL DEFAULT 1,
    granted_by INTEGER,
    expires_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES auth.permissions(id) ON DELETE CASCADE,
    UNIQUE (user_id, permission_id)
);

CREATE INDEX IF NOT EXISTS idx_user_permissions_user ON auth.user_permissions(user_id);
CREATE INDEX IF NOT EXISTS idx_user_permissions_permission ON auth.user_permissions(permission_id);
CREATE INDEX IF NOT EXISTS idx_user_permissions_granted ON auth.user_permissions(granted);
CREATE INDEX IF NOT EXISTS idx_user_permissions_expires ON auth.user_permissions(expires_at);

-- ============== PERMISSION ATTRIBUTES TABLE ==============
-- Additional metadata for permissions (conditions, constraints)
CREATE TABLE IF NOT EXISTS auth.permission_attributes
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    permission_id INTEGER NOT NULL,
    attribute_key VARCHAR(100) NOT NULL,
    attribute_value TEXT,
    attribute_type VARCHAR(50) DEFAULT 'string',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (permission_id) REFERENCES auth.permissions(id) ON DELETE CASCADE,
    UNIQUE (permission_id, attribute_key)
);

CREATE INDEX IF NOT EXISTS idx_permission_attributes_permission ON auth.permission_attributes(permission_id);
CREATE INDEX IF NOT EXISTS idx_permission_attributes_key ON auth.permission_attributes(attribute_key);

-- ============== PERMISSION SETTINGS TABLE ==============
-- Store system-wide permission configuration
CREATE TABLE IF NOT EXISTS auth.permission_settings
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT,
    description TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============== PERMISSION AUDIT LOG TABLE ==============
-- Tracks all permission changes for compliance
CREATE TABLE IF NOT EXISTS auth.permission_audit_log
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action VARCHAR(100) NOT NULL,
    resource_type VARCHAR(100),
    resource_id INTEGER,
    old_value TEXT,
    new_value TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    performed_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_audit_log_user ON auth.permission_audit_log(user_id);
CREATE INDEX IF NOT EXISTS idx_audit_log_action ON auth.permission_audit_log(action);
CREATE INDEX IF NOT EXISTS idx_audit_log_resource ON auth.permission_audit_log(resource_type, resource_id);
CREATE INDEX IF NOT EXISTS idx_audit_log_created ON auth.permission_audit_log(created_at);

-- ============== INSERT DEFAULT DATA ==============

-- Insert default roles
INSERT INTO auth.roles (name, display_name, description, level, is_active) VALUES
('super_admin', 'Super Administrator', 'Full system access with all permissions', 100, 1),
('admin', 'Administrator', 'Administrative access to manage system', 90, 1),
('manager', 'Manager', 'Managerial access with team oversight', 70, 1),
('supervisor', 'Supervisor', 'Supervisory access with limited management', 60, 1),
('staff', 'Staff', 'Standard employee access', 50, 1),
('user', 'User', 'Basic user access', 30, 1),
('guest', 'Guest', 'Limited guest access', 10, 1);

-- Insert default permissions

-- User Management Permissions
INSERT INTO auth.permissions (name, display_name, description, module, category, is_active) VALUES
('users.view', 'View Users', 'View user listings and profiles', 'users', 'read', 1),
('users.create', 'Create Users', 'Create new users', 'users', 'write', 1),
('users.update', 'Update Users', 'Update user information', 'users', 'write', 1),
('users.delete', 'Delete Users', 'Delete users from system', 'users', 'write', 1),
('users.manage', 'Manage Users', 'Full user management access', 'users', 'admin', 1);

-- Role Management Permissions
INSERT INTO auth.permissions (name, display_name, description, module, category, is_active) VALUES
('roles.view', 'View Roles', 'View role listings', 'roles', 'read', 1),
('roles.create', 'Create Roles', 'Create new roles', 'roles', 'write', 1),
('roles.update', 'Update Roles', 'Update role information', 'roles', 'write', 1),
('roles.delete', 'Delete Roles', 'Delete roles', 'roles', 'write', 1),
('roles.assign', 'Assign Roles', 'Assign roles to users', 'roles', 'write', 1),
('roles.manage', 'Manage Roles', 'Full role management access', 'roles', 'admin', 1);

-- Permission Management Permissions
INSERT INTO auth.permissions (name, display_name, description, module, category, is_active) VALUES
('permissions.view', 'View Permissions', 'View permission listings', 'permissions', 'read', 1),
('permissions.create', 'Create Permissions', 'Create new permissions', 'permissions', 'write', 1),
('permissions.update', 'Update Permissions', 'Update permission information', 'permissions', 'write', 1),
('permissions.delete', 'Delete Permissions', 'Delete permissions', 'permissions', 'write', 1),
('permissions.grant', 'Grant Permissions', 'Grant permissions to users/roles', 'permissions', 'write', 1),
('permissions.revoke', 'Revoke Permissions', 'Revoke permissions from users/roles', 'permissions', 'write', 1),
('permissions.manage', 'Manage Permissions', 'Full permission management access', 'permissions', 'admin', 1);

-- Project Management Permissions
INSERT INTO auth.permissions (name, display_name, description, module, category, is_active) VALUES
('projects.view', 'View Projects', 'View project listings', 'projects', 'read', 1),
('projects.create', 'Create Projects', 'Create new projects', 'projects', 'write', 1),
('projects.update', 'Update Projects', 'Update project information', 'projects', 'write', 1),
('projects.delete', 'Delete Projects', 'Delete projects', 'projects', 'write', 1),
('projects.approve', 'Approve Projects', 'Approve project submissions', 'projects', 'admin', 1),
('projects.manage', 'Manage Projects', 'Full project management access', 'projects', 'admin', 1);

-- System Settings Permissions
INSERT INTO auth.permissions (name, display_name, description, module, category, is_active) VALUES
('settings.view', 'View Settings', 'View system settings', 'settings', 'read', 1),
('settings.update', 'Update Settings', 'Update system settings', 'settings', 'write', 1),
('settings.manage', 'Manage Settings', 'Full settings management', 'settings', 'admin', 1);

-- Audit Log Permissions
INSERT INTO auth.permissions (name, display_name, description, module, category, is_active) VALUES
('audit.view', 'View Audit Logs', 'View system audit logs', 'audit', 'read', 1),
('audit.export', 'Export Audit Logs', 'Export audit log data', 'audit', 'write', 1),
('audit.manage', 'Manage Audit Logs', 'Full audit log management', 'audit', 'admin', 1);

-- Reports Permissions
INSERT INTO auth.permissions (name, display_name, description, module, category, is_active) VALUES
('reports.view', 'View Reports', 'View system reports', 'reports', 'read', 1),
('reports.create', 'Create Reports', 'Create custom reports', 'reports', 'write', 1),
('reports.export', 'Export Reports', 'Export report data', 'reports', 'write', 1),
('reports.manage', 'Manage Reports', 'Full report management', 'reports', 'admin', 1);

-- Set up role hierarchy (parent inherits from child)
-- super_admin > admin > manager > supervisor > staff > user
INSERT INTO auth.role_hierarchies (parent_role_id, child_role_id)
SELECT
    (SELECT id FROM auth.roles WHERE name = 'super_admin'),
    (SELECT id FROM auth.roles WHERE name = 'admin');

INSERT INTO auth.role_hierarchies (parent_role_id, child_role_id)
SELECT
    (SELECT id FROM auth.roles WHERE name = 'admin'),
    (SELECT id FROM auth.roles WHERE name = 'manager');

INSERT INTO auth.role_hierarchies (parent_role_id, child_role_id)
SELECT
    (SELECT id FROM auth.roles WHERE name = 'manager'),
    (SELECT id FROM auth.roles WHERE name = 'supervisor');

INSERT INTO auth.role_hierarchies (parent_role_id, child_role_id)
SELECT
    (SELECT id FROM auth.roles WHERE name = 'supervisor'),
    (SELECT id FROM auth.roles WHERE name = 'staff');

INSERT INTO auth.role_hierarchies (parent_role_id, child_role_id)
SELECT
    (SELECT id FROM auth.roles WHERE name = 'staff'),
    (SELECT id FROM auth.roles WHERE name = 'user');

-- Assign permissions to roles

-- Super Admin: ALL permissions
INSERT INTO auth.role_permissions (role_id, permission_id)
SELECT
    (SELECT id FROM auth.roles WHERE name = 'super_admin'),
    id
FROM auth.permissions;

-- Admin: All except super admin permissions
INSERT INTO auth.role_permissions (role_id, permission_id)
SELECT
    (SELECT id FROM auth.roles WHERE name = 'admin'),
    id
FROM auth.permissions
WHERE name NOT IN ('permissions.manage', 'roles.manage');

-- Manager: View and manage projects, users (limited)
INSERT INTO auth.role_permissions (role_id, permission_id)
SELECT
    (SELECT id FROM auth.roles WHERE name = 'manager'),
    id
FROM auth.permissions
WHERE name IN (
    'users.view', 'users.update',
    'projects.view', 'projects.create', 'projects.update', 'projects.approve',
    'reports.view', 'reports.create', 'reports.export'
);

-- Supervisor: View and update projects
INSERT INTO auth.role_permissions (role_id, permission_id)
SELECT
    (SELECT id FROM auth.roles WHERE name = 'supervisor'),
    id
FROM auth.permissions
WHERE name IN (
    'users.view',
    'projects.view', 'projects.update',
    'reports.view'
);

-- Staff: View and create projects
INSERT INTO auth.role_permissions (role_id, permission_id)
SELECT
    (SELECT id FROM auth.roles WHERE name = 'staff'),
    id
FROM auth.permissions
WHERE name IN (
    'projects.view', 'projects.create',
    'reports.view'
);

-- User: Basic view permissions
INSERT INTO auth.role_permissions (role_id, permission_id)
SELECT
    (SELECT id FROM auth.roles WHERE name = 'user'),
    id
FROM auth.permissions
WHERE name IN (
    'projects.view',
    'reports.view'
);

-- Guest: Very limited view permissions
INSERT INTO auth.role_permissions (role_id, permission_id)
SELECT
    (SELECT id FROM auth.roles WHERE name = 'guest'),
    id
FROM auth.permissions
WHERE name IN ('projects.view');

-- Insert system settings
INSERT INTO auth.permission_settings (setting_key, setting_value, description) VALUES
('source', 'database', 'Permission source: database, config, or both'),
('cache_enabled', 'true', 'Enable permission caching'),
('cache_ttl', '3600', 'Cache time-to-live in seconds'),
('audit_enabled', 'true', 'Enable permission audit logging');

-- ============== HELPER VIEWS ==============

-- View to get all permissions for a role (including inherited)
CREATE VIEW IF NOT EXISTS auth.role_permissions_with_hierarchy AS
SELECT DISTINCT
    r.id as role_id,
    r.name as role_name,
    r.display_name as role_display_name,
    p.id as permission_id,
    p.name as permission_name,
    p.display_name as permission_display_name,
    p.module,
    CASE
        WHEN rp.role_id = r.id THEN 'direct'
        ELSE 'inherited'
    END as assignment_type
FROM auth.roles r
LEFT JOIN auth.role_hierarchies rh ON r.id = rh.parent_role_id
LEFT JOIN auth.role_permissions rp ON (rp.role_id = r.id OR rp.role_id = rh.child_role_id)
LEFT JOIN auth.permissions p ON rp.permission_id = p.id
WHERE r.is_active = 1 AND (p.id IS NULL OR p.is_active = 1);

-- View to get all user permissions (from roles and direct assignments)
CREATE VIEW IF NOT EXISTS auth.user_permissions_complete AS
SELECT DISTINCT
    ur.user_id,
    p.id as permission_id,
    p.name as permission_name,
    p.display_name as permission_display_name,
    p.module,
    p.category,
    'role' as assignment_type,
    r.name as role_name
FROM auth.role_user ur
JOIN auth.roles r ON ur.role_id = r.id
LEFT JOIN auth.role_permissions_with_hierarchy rph ON rph.role_id = r.id
LEFT JOIN auth.permissions p ON p.id = rph.permission_id
WHERE r.is_active = 1
  AND (p.id IS NULL OR p.is_active = 1)

UNION

SELECT
    up.user_id,
    p.id as permission_id,
    p.name as permission_name,
    p.display_name as permission_display_name,
    p.module,
    p.category,
    'direct' as assignment_type,
    NULL as role_name
FROM auth.user_permissions up
JOIN auth.permissions p ON up.permission_id = p.id
WHERE (up.expires_at IS NULL OR up.expires_at > datetime('now'))
  AND up.granted = 1
  AND p.is_active = 1;

-- Role permission summary view
CREATE VIEW IF NOT EXISTS auth.role_permission_summary AS
SELECT
    r.id as role_id,
    r.name as role_name,
    r.display_name,
    r.level,
    COUNT(DISTINCT rp.permission_id) as permission_count,
    COUNT(DISTINCT ur.user_id) as user_count
FROM auth.roles r
LEFT JOIN auth.role_permissions rp ON r.id = rp.role_id
LEFT JOIN auth.role_user ur ON r.id = ur.role_id
WHERE r.is_active = 1
GROUP BY r.id, r.name, r.display_name, r.level
ORDER BY r.level DESC, r.name;

-- Permission usage statistics
CREATE VIEW IF NOT EXISTS auth.permission_usage_stats AS
SELECT
    p.id as permission_id,
    p.name as permission_name,
    p.module,
    p.category,
    COUNT(DISTINCT rp.role_id) as assigned_to_roles,
    COUNT(DISTINCT up.user_id) as directly_assigned_users,
    p.is_active
FROM auth.permissions p
LEFT JOIN auth.role_permissions rp ON p.id = rp.permission_id
LEFT JOIN auth.user_permissions up ON p.id = up.permission_id AND up.granted = 1
GROUP BY p.id, p.name, p.module, p.category, p.is_active
ORDER BY assigned_to_roles DESC, directly_assigned_users DESC, p.name;

COMMIT;
