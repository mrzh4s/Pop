-- Permission System Seed Data (PostgreSQL)
-- File: docs/permission/permission.seed.sql
-- Run this after permission.table.sql
-- All tables are under the auth schema

BEGIN;

-- ============== PERMISSIONS SEED DATA ==============

-- User Management Permissions
INSERT INTO auth.permissions (name, display_name, description, module, category, is_active) VALUES
('users.view', 'View Users', 'View user listings and profiles', 'users', 'read', true),
('users.create', 'Create Users', 'Create new users', 'users', 'write', true),
('users.update', 'Update Users', 'Update user information', 'users', 'write', true),
('users.delete', 'Delete Users', 'Delete users from system', 'users', 'write', true),
('users.manage', 'Manage Users', 'Full user management access', 'users', 'admin', true)
ON CONFLICT (name) DO UPDATE SET
    display_name = EXCLUDED.display_name,
    description = EXCLUDED.description,
    updated_at = NOW();

-- Role Management Permissions
INSERT INTO auth.permissions (name, display_name, description, module, category, is_active) VALUES
('roles.view', 'View Roles', 'View role listings', 'roles', 'read', true),
('roles.create', 'Create Roles', 'Create new roles', 'roles', 'write', true),
('roles.update', 'Update Roles', 'Update role information', 'roles', 'write', true),
('roles.delete', 'Delete Roles', 'Delete roles', 'roles', 'write', true),
('roles.assign', 'Assign Roles', 'Assign roles to users', 'roles', 'write', true),
('roles.manage', 'Manage Roles', 'Full role management access', 'roles', 'admin', true)
ON CONFLICT (name) DO UPDATE SET
    display_name = EXCLUDED.display_name,
    description = EXCLUDED.description,
    updated_at = NOW();

-- Permission Management Permissions
INSERT INTO auth.permissions (name, display_name, description, module, category, is_active) VALUES
('permissions.view', 'View Permissions', 'View permission listings', 'permissions', 'read', true),
('permissions.create', 'Create Permissions', 'Create new permissions', 'permissions', 'write', true),
('permissions.update', 'Update Permissions', 'Update permission information', 'permissions', 'write', true),
('permissions.delete', 'Delete Permissions', 'Delete permissions', 'permissions', 'write', true),
('permissions.grant', 'Grant Permissions', 'Grant permissions to users/roles', 'permissions', 'write', true),
('permissions.revoke', 'Revoke Permissions', 'Revoke permissions from users/roles', 'permissions', 'write', true),
('permissions.manage', 'Manage Permissions', 'Full permission management access', 'permissions', 'admin', true)
ON CONFLICT (name) DO UPDATE SET
    display_name = EXCLUDED.display_name,
    description = EXCLUDED.description,
    updated_at = NOW();

-- Project Management Permissions
INSERT INTO auth.permissions (name, display_name, description, module, category, is_active) VALUES
('projects.view', 'View Projects', 'View project listings', 'projects', 'read', true),
('projects.create', 'Create Projects', 'Create new projects', 'projects', 'write', true),
('projects.update', 'Update Projects', 'Update project information', 'projects', 'write', true),
('projects.delete', 'Delete Projects', 'Delete projects', 'projects', 'write', true),
('projects.approve', 'Approve Projects', 'Approve project submissions', 'projects', 'admin', true),
('projects.manage', 'Manage Projects', 'Full project management access', 'projects', 'admin', true)
ON CONFLICT (name) DO UPDATE SET
    display_name = EXCLUDED.display_name,
    description = EXCLUDED.display_name,
    updated_at = NOW();

-- System Settings Permissions
INSERT INTO auth.permissions (name, display_name, description, module, category, is_active) VALUES
('settings.view', 'View Settings', 'View system settings', 'settings', 'read', true),
('settings.update', 'Update Settings', 'Update system settings', 'settings', 'write', true),
('settings.manage', 'Manage Settings', 'Full settings management', 'settings', 'admin', true)
ON CONFLICT (name) DO UPDATE SET
    display_name = EXCLUDED.display_name,
    description = EXCLUDED.description,
    updated_at = NOW();

-- Audit Log Permissions
INSERT INTO auth.permissions (name, display_name, description, module, category, is_active) VALUES
('audit.view', 'View Audit Logs', 'View system audit logs', 'audit', 'read', true),
('audit.export', 'Export Audit Logs', 'Export audit log data', 'audit', 'write', true),
('audit.manage', 'Manage Audit Logs', 'Full audit log management', 'audit', 'admin', true)
ON CONFLICT (name) DO UPDATE SET
    display_name = EXCLUDED.display_name,
    description = EXCLUDED.description,
    updated_at = NOW();

-- Reports Permissions
INSERT INTO auth.permissions (name, display_name, description, module, category, is_active) VALUES
('reports.view', 'View Reports', 'View system reports', 'reports', 'read', true),
('reports.create', 'Create Reports', 'Create custom reports', 'reports', 'write', true),
('reports.export', 'Export Reports', 'Export report data', 'reports', 'write', true),
('reports.manage', 'Manage Reports', 'Full report management', 'reports', 'admin', true)
ON CONFLICT (name) DO UPDATE SET
    display_name = EXCLUDED.display_name,
    description = EXCLUDED.description,
    updated_at = NOW();

-- ============== ROLE HIERARCHIES ==============
-- Note: Uses existing auth.roles from auth.table.sql

DO $$
DECLARE
    super_admin_id bigint;
    admin_id bigint;
    manager_id bigint;
    supervisor_id bigint;
    staff_id bigint;
    user_id bigint;
BEGIN
    -- Get role IDs from existing auth.roles table
    SELECT id INTO super_admin_id FROM auth.roles WHERE name = 'super_admin';
    SELECT id INTO admin_id FROM auth.roles WHERE name = 'admin';
    SELECT id INTO manager_id FROM auth.roles WHERE name = 'manager';
    SELECT id INTO supervisor_id FROM auth.roles WHERE name = 'supervisor';
    SELECT id INTO staff_id FROM auth.roles WHERE name = 'staff';
    SELECT id INTO user_id FROM auth.roles WHERE name = 'user' OR name = 'client';

    -- Only proceed if we have valid role IDs
    IF super_admin_id IS NOT NULL AND admin_id IS NOT NULL THEN
        -- Define hierarchies: super_admin > admin > manager > supervisor > staff
        INSERT INTO auth.role_hierarchies (parent_role_id, child_role_id) VALUES
            (super_admin_id, admin_id)
        ON CONFLICT (parent_role_id, child_role_id) DO NOTHING;
    END IF;

    IF admin_id IS NOT NULL AND manager_id IS NOT NULL THEN
        INSERT INTO auth.role_hierarchies (parent_role_id, child_role_id) VALUES
            (admin_id, manager_id)
        ON CONFLICT (parent_role_id, child_role_id) DO NOTHING;
    END IF;

    IF manager_id IS NOT NULL AND supervisor_id IS NOT NULL THEN
        INSERT INTO auth.role_hierarchies (parent_role_id, child_role_id) VALUES
            (manager_id, supervisor_id)
        ON CONFLICT (parent_role_id, child_role_id) DO NOTHING;
    END IF;

    IF supervisor_id IS NOT NULL AND staff_id IS NOT NULL THEN
        INSERT INTO auth.role_hierarchies (parent_role_id, child_role_id) VALUES
            (supervisor_id, staff_id)
        ON CONFLICT (parent_role_id, child_role_id) DO NOTHING;
    END IF;

    RAISE NOTICE 'Role hierarchies created successfully!';
END $$;

-- ============== ROLE PERMISSIONS ASSIGNMENTS ==============

DO $$
DECLARE
    super_admin_id bigint;
    admin_id bigint;
    manager_id bigint;
    supervisor_id bigint;
    staff_id bigint;
BEGIN
    -- Get role IDs
    SELECT id INTO super_admin_id FROM auth.roles WHERE name = 'super_admin';
    SELECT id INTO admin_id FROM auth.roles WHERE name = 'admin';
    SELECT id INTO manager_id FROM auth.roles WHERE name = 'manager';
    SELECT id INTO supervisor_id FROM auth.roles WHERE name = 'supervisor';
    SELECT id INTO staff_id FROM auth.roles WHERE name = 'staff' OR name = 'senior_staff';

    -- Super Admin: ALL permissions
    IF super_admin_id IS NOT NULL THEN
        INSERT INTO auth.role_permissions (role_id, permission_id)
        SELECT super_admin_id, id FROM auth.permissions
        ON CONFLICT (role_id, permission_id) DO NOTHING;
    END IF;

    -- Admin: All except super admin permissions
    IF admin_id IS NOT NULL THEN
        INSERT INTO auth.role_permissions (role_id, permission_id)
        SELECT admin_id, id FROM auth.permissions
        WHERE name NOT IN ('permissions.manage', 'roles.manage')
        ON CONFLICT (role_id, permission_id) DO NOTHING;
    END IF;

    -- Manager: View and manage projects, users (limited)
    IF manager_id IS NOT NULL THEN
        INSERT INTO auth.role_permissions (role_id, permission_id)
        SELECT manager_id, id FROM auth.permissions
        WHERE name IN (
            'users.view', 'users.update',
            'projects.view', 'projects.create', 'projects.update', 'projects.approve',
            'reports.view', 'reports.create', 'reports.export'
        )
        ON CONFLICT (role_id, permission_id) DO NOTHING;
    END IF;

    -- Supervisor: View and update projects
    IF supervisor_id IS NOT NULL THEN
        INSERT INTO auth.role_permissions (role_id, permission_id)
        SELECT supervisor_id, id FROM auth.permissions
        WHERE name IN (
            'users.view',
            'projects.view', 'projects.update',
            'reports.view'
        )
        ON CONFLICT (role_id, permission_id) DO NOTHING;
    END IF;

    -- Staff: View and create projects
    IF staff_id IS NOT NULL THEN
        INSERT INTO auth.role_permissions (role_id, permission_id)
        SELECT staff_id, id FROM auth.permissions
        WHERE name IN (
            'projects.view', 'projects.create',
            'reports.view'
        )
        ON CONFLICT (role_id, permission_id) DO NOTHING;
    END IF;

    RAISE NOTICE 'Permission seed data created successfully!';
    RAISE NOTICE 'Permissions assigned based on role levels';
END $$;

-- ============== PERMISSION SETTINGS ==============
INSERT INTO auth.permission_settings (setting_key, setting_value, description) VALUES
('source', 'database', 'Permission source: database, config, or both'),
('cache_enabled', 'true', 'Enable permission caching'),
('cache_ttl', '3600', 'Cache time-to-live in seconds'),
('audit_enabled', 'true', 'Enable permission audit logging')
ON CONFLICT (setting_key) DO UPDATE SET
    setting_value = EXCLUDED.setting_value,
    updated_at = NOW();

COMMIT;

-- Display summary
SELECT 'Permission Seed Data Summary' as info;
SELECT COUNT(*) as total_permissions FROM auth.permissions;
SELECT COUNT(*) as total_role_hierarchies FROM auth.role_hierarchies;
SELECT COUNT(*) as total_role_permission_assignments FROM auth.role_permissions;

-- Show role permission summary
SELECT * FROM auth.role_permission_summary ORDER BY role_name;
