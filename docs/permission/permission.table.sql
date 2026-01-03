-- Permission System Schema (PostgreSQL)
-- File: docs/permission/permission.table.sql
-- All tables are under the auth schema alongside user/role/group tables
BEGIN;

-- Auth schema should already exist from auth.table.sql
-- If running standalone, uncomment below:
-- CREATE SCHEMA IF NOT EXISTS auth;

-- Drop tables if existing (to allow re-run)
DROP TABLE IF EXISTS auth.permission_audit_log;
DROP TABLE IF EXISTS auth.permission_attributes;
DROP TABLE IF EXISTS auth.permission_settings;
DROP TABLE IF EXISTS auth.user_permissions;
DROP TABLE IF EXISTS auth.user_roles;
DROP TABLE IF EXISTS auth.role_permissions;
DROP TABLE IF EXISTS auth.role_hierarchies;
DROP TABLE IF EXISTS auth.permissions;
-- Note: auth.roles already exists from auth.table.sql, we'll add columns if needed

-- ============== PERMISSIONS TABLE ==============
-- Individual permissions/capabilities
CREATE TABLE IF NOT EXISTS auth.permissions
(
    id bigserial NOT NULL,
    name character varying(255) COLLATE pg_catalog."default" NOT NULL,
    display_name character varying(255) COLLATE pg_catalog."default" NOT NULL,
    description text COLLATE pg_catalog."default",
    module character varying(100) COLLATE pg_catalog."default",
    category character varying(100) COLLATE pg_catalog."default",
    is_active boolean NOT NULL DEFAULT true,
    created_at timestamp(0) without time zone DEFAULT now(),
    updated_at timestamp(0) without time zone DEFAULT now(),
    CONSTRAINT permissions_pkey PRIMARY KEY (id),
    CONSTRAINT permissions_name_unique UNIQUE (name)
);

-- ============== ROLE HIERARCHIES TABLE ==============
-- Defines parent-child relationships between roles
CREATE TABLE IF NOT EXISTS auth.role_hierarchies
(
    id bigserial NOT NULL,
    parent_role_id bigint NOT NULL,
    child_role_id bigint NOT NULL,
    created_at timestamp(0) without time zone DEFAULT now(),
    updated_at timestamp(0) without time zone DEFAULT now(),
    CONSTRAINT role_hierarchies_pkey PRIMARY KEY (id),
    CONSTRAINT role_hierarchies_parent_child_unique UNIQUE (parent_role_id, child_role_id)
);

-- ============== ROLE PERMISSIONS TABLE ==============
-- Many-to-many relationship between roles and permissions
CREATE TABLE IF NOT EXISTS auth.role_permissions
(
    id bigserial NOT NULL,
    role_id bigint NOT NULL,
    permission_id bigint NOT NULL,
    granted_by bigint,
    created_at timestamp(0) without time zone DEFAULT now(),
    updated_at timestamp(0) without time zone DEFAULT now(),
    CONSTRAINT role_permissions_pkey PRIMARY KEY (id),
    CONSTRAINT role_permissions_role_permission_unique UNIQUE (role_id, permission_id)
);

-- Note: auth.user_roles already exists in auth.table.sql as auth.role_user
-- We create a view to alias it for consistency
CREATE OR REPLACE VIEW auth.user_roles AS
SELECT
    id,
    user_id,
    role_id,
    created_at,
    updated_at,
    NULL::bigint as assigned_by,
    NULL::timestamp as expires_at
FROM auth.role_user;

-- ============== USER PERMISSIONS TABLE ==============
-- Direct permission assignments to users (overrides)
CREATE TABLE IF NOT EXISTS auth.user_permissions
(
    id bigserial NOT NULL,
    user_id uuid NOT NULL,
    permission_id bigint NOT NULL,
    granted boolean NOT NULL DEFAULT true,
    granted_by bigint,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone DEFAULT now(),
    updated_at timestamp(0) without time zone DEFAULT now(),
    CONSTRAINT user_permissions_pkey PRIMARY KEY (id),
    CONSTRAINT user_permissions_user_permission_unique UNIQUE (user_id, permission_id)
);

-- ============== PERMISSION ATTRIBUTES TABLE ==============
-- Additional metadata for permissions (conditions, constraints)
CREATE TABLE IF NOT EXISTS auth.permission_attributes
(
    id bigserial NOT NULL,
    permission_id bigint NOT NULL,
    attribute_key character varying(100) COLLATE pg_catalog."default" NOT NULL,
    attribute_value text COLLATE pg_catalog."default",
    attribute_type character varying(50) COLLATE pg_catalog."default" DEFAULT 'string',
    created_at timestamp(0) without time zone DEFAULT now(),
    updated_at timestamp(0) without time zone DEFAULT now(),
    CONSTRAINT permission_attributes_pkey PRIMARY KEY (id),
    CONSTRAINT permission_attributes_permission_key_unique UNIQUE (permission_id, attribute_key)
);

-- ============== PERMISSION SETTINGS TABLE ==============
-- Store system-wide permission configuration
CREATE TABLE IF NOT EXISTS auth.permission_settings
(
    id bigserial NOT NULL,
    setting_key character varying(50) COLLATE pg_catalog."default" NOT NULL UNIQUE,
    setting_value text COLLATE pg_catalog."default",
    description text COLLATE pg_catalog."default",
    updated_at timestamp(0) without time zone DEFAULT now(),
    CONSTRAINT permission_settings_pkey PRIMARY KEY (id)
);

-- ============== PERMISSION AUDIT LOG TABLE ==============
-- Tracks all permission changes for compliance
CREATE TABLE IF NOT EXISTS auth.permission_audit_log
(
    id bigserial NOT NULL,
    user_id uuid,
    action character varying(100) COLLATE pg_catalog."default" NOT NULL,
    resource_type character varying(100) COLLATE pg_catalog."default",
    resource_id bigint,
    old_value jsonb,
    new_value jsonb,
    ip_address inet,
    user_agent text COLLATE pg_catalog."default",
    performed_by bigint,
    created_at timestamp(0) without time zone DEFAULT now(),
    CONSTRAINT permission_audit_log_pkey PRIMARY KEY (id)
);

-- ============== ADD FOREIGN KEYS AND INDEXES ==============

-- Role Hierarchies
ALTER TABLE IF EXISTS auth.role_hierarchies
    ADD CONSTRAINT role_hierarchies_parent_role_foreign FOREIGN KEY (parent_role_id)
    REFERENCES auth.roles (id) MATCH SIMPLE
    ON UPDATE NO ACTION
    ON DELETE CASCADE;

ALTER TABLE IF EXISTS auth.role_hierarchies
    ADD CONSTRAINT role_hierarchies_child_role_foreign FOREIGN KEY (child_role_id)
    REFERENCES auth.roles (id) MATCH SIMPLE
    ON UPDATE NO ACTION
    ON DELETE CASCADE;

CREATE INDEX IF NOT EXISTS role_hierarchies_parent_role_index
    ON auth.role_hierarchies(parent_role_id);

CREATE INDEX IF NOT EXISTS role_hierarchies_child_role_index
    ON auth.role_hierarchies(child_role_id);

-- Role Permissions
ALTER TABLE IF EXISTS auth.role_permissions
    ADD CONSTRAINT role_permissions_role_foreign FOREIGN KEY (role_id)
    REFERENCES auth.roles (id) MATCH SIMPLE
    ON UPDATE NO ACTION
    ON DELETE CASCADE;

ALTER TABLE IF EXISTS auth.role_permissions
    ADD CONSTRAINT role_permissions_permission_foreign FOREIGN KEY (permission_id)
    REFERENCES auth.permissions (id) MATCH SIMPLE
    ON UPDATE NO ACTION
    ON DELETE CASCADE;

CREATE INDEX IF NOT EXISTS role_permissions_role_index
    ON auth.role_permissions(role_id);

CREATE INDEX IF NOT EXISTS role_permissions_permission_index
    ON auth.role_permissions(permission_id);

-- User Permissions (references auth.users)
ALTER TABLE IF EXISTS auth.user_permissions
    ADD CONSTRAINT user_permissions_user_foreign FOREIGN KEY (user_id)
    REFERENCES auth.users (id) MATCH SIMPLE
    ON UPDATE NO ACTION
    ON DELETE CASCADE;

ALTER TABLE IF EXISTS auth.user_permissions
    ADD CONSTRAINT user_permissions_permission_foreign FOREIGN KEY (permission_id)
    REFERENCES auth.permissions (id) MATCH SIMPLE
    ON UPDATE NO ACTION
    ON DELETE CASCADE;

CREATE INDEX IF NOT EXISTS user_permissions_user_index
    ON auth.user_permissions(user_id);

CREATE INDEX IF NOT EXISTS user_permissions_permission_index
    ON auth.user_permissions(permission_id);

CREATE INDEX IF NOT EXISTS user_permissions_granted_index
    ON auth.user_permissions(granted);

CREATE INDEX IF NOT EXISTS user_permissions_expires_at_index
    ON auth.user_permissions(expires_at);

-- Permission Attributes
ALTER TABLE IF EXISTS auth.permission_attributes
    ADD CONSTRAINT permission_attributes_permission_foreign FOREIGN KEY (permission_id)
    REFERENCES auth.permissions (id) MATCH SIMPLE
    ON UPDATE NO ACTION
    ON DELETE CASCADE;

CREATE INDEX IF NOT EXISTS permission_attributes_permission_index
    ON auth.permission_attributes(permission_id);

CREATE INDEX IF NOT EXISTS permission_attributes_key_index
    ON auth.permission_attributes(attribute_key);

-- Permissions Indexes
CREATE INDEX IF NOT EXISTS permissions_name_index
    ON auth.permissions(name);

CREATE INDEX IF NOT EXISTS permissions_module_index
    ON auth.permissions(module);

CREATE INDEX IF NOT EXISTS permissions_category_index
    ON auth.permissions(category);

CREATE INDEX IF NOT EXISTS permissions_is_active_index
    ON auth.permissions(is_active);

-- Audit Log Indexes
CREATE INDEX IF NOT EXISTS permission_audit_log_user_index
    ON auth.permission_audit_log(user_id);

CREATE INDEX IF NOT EXISTS permission_audit_log_action_index
    ON auth.permission_audit_log(action);

CREATE INDEX IF NOT EXISTS permission_audit_log_resource_index
    ON auth.permission_audit_log(resource_type, resource_id);

CREATE INDEX IF NOT EXISTS permission_audit_log_created_at_index
    ON auth.permission_audit_log(created_at);

-- ============== HELPER VIEWS ==============

-- User permission summary view
CREATE OR REPLACE VIEW auth.user_permission_summary AS
SELECT
    u.id as user_id,
    u.name as user_name,
    u.email,
    string_agg(DISTINCT r.display_name, ', ' ORDER BY r.display_name) as roles,
    COUNT(DISTINCT ru.role_id) as role_count,
    COUNT(DISTINCT up.permission_id) as direct_permission_count,
    MAX(up.expires_at) as permission_expires_at
FROM auth.users u
LEFT JOIN auth.role_user ru ON u.id = ru.user_id
LEFT JOIN auth.roles r ON ru.role_id = r.id AND r.is_active = true
LEFT JOIN auth.user_permissions up ON u.id = up.user_id AND (up.expires_at IS NULL OR up.expires_at > now())
WHERE u.is_active = true
GROUP BY u.id, u.name, u.email
ORDER BY u.name;

-- Role permission summary view
CREATE OR REPLACE VIEW auth.role_permission_summary AS
SELECT
    r.id as role_id,
    r.name as role_name,
    r.display_name,
    COUNT(DISTINCT rp.permission_id) as permission_count,
    COUNT(DISTINCT ru.user_id) as user_count,
    string_agg(DISTINCT p.display_name, ', ' ORDER BY p.display_name) as permissions
FROM auth.roles r
LEFT JOIN auth.role_permissions rp ON r.id = rp.role_id
LEFT JOIN auth.permissions p ON rp.permission_id = p.id AND p.is_active = true
LEFT JOIN auth.role_user ru ON r.id = ru.role_id
WHERE r.is_active = true
GROUP BY r.id, r.name, r.display_name
ORDER BY r.name;

-- Permission usage statistics
CREATE OR REPLACE VIEW auth.permission_usage_stats AS
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
LEFT JOIN auth.user_permissions up ON p.id = up.permission_id AND up.granted = true
GROUP BY p.id, p.name, p.module, p.category, p.is_active
ORDER BY assigned_to_roles DESC, directly_assigned_users DESC, p.name;

-- Role hierarchy tree view
CREATE OR REPLACE VIEW auth.role_hierarchy_tree AS
WITH RECURSIVE hierarchy AS (
    SELECT
        r.id,
        r.name,
        r.display_name,
        NULL::bigint as parent_id,
        NULL::text as parent_name,
        0 as depth
    FROM auth.roles r
    WHERE r.id NOT IN (SELECT child_role_id FROM auth.role_hierarchies)
      AND r.is_active = true

    UNION ALL

    SELECT
        r.id,
        r.name,
        r.display_name,
        rh.parent_role_id as parent_id,
        pr.name as parent_name,
        h.depth + 1
    FROM auth.roles r
    JOIN auth.role_hierarchies rh ON r.id = rh.child_role_id
    JOIN auth.roles pr ON rh.parent_role_id = pr.id
    JOIN hierarchy h ON rh.parent_role_id = h.id
    WHERE r.is_active = true
)
SELECT * FROM hierarchy
ORDER BY depth, name;

-- View to get all permissions for a role (including inherited)
CREATE OR REPLACE VIEW auth.role_permissions_with_hierarchy AS
SELECT DISTINCT
    r.id as role_id,
    r.name as role_name,
    r.display_name as role_display_name,
    p.id as permission_id,
    p.name as permission_name,
    p.display_name as permission_display_name,
    p.module,
    p.category,
    CASE
        WHEN rp.role_id = r.id THEN 'direct'
        ELSE 'inherited'
    END as assignment_type
FROM auth.roles r
LEFT JOIN auth.role_hierarchies rh ON r.id = rh.parent_role_id
LEFT JOIN auth.role_permissions rp ON (rp.role_id = r.id OR rp.role_id = rh.child_role_id)
LEFT JOIN auth.permissions p ON rp.permission_id = p.id
WHERE r.is_active = true AND (p.id IS NULL OR p.is_active = true);

-- View to get all user permissions (from roles and direct assignments)
CREATE OR REPLACE VIEW auth.user_permissions_complete AS
SELECT DISTINCT
    ru.user_id,
    p.id as permission_id,
    p.name as permission_name,
    p.display_name as permission_display_name,
    p.module,
    p.category,
    'role' as assignment_type,
    r.name as role_name
FROM auth.role_user ru
JOIN auth.roles r ON ru.role_id = r.id
LEFT JOIN auth.role_permissions_with_hierarchy rph ON rph.role_id = r.id
LEFT JOIN auth.permissions p ON p.id = rph.permission_id
WHERE r.is_active = true
  AND (p.id IS NULL OR p.is_active = true)

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
WHERE (up.expires_at IS NULL OR up.expires_at > now())
  AND up.granted = true
  AND p.is_active = true;

-- Recent audit log view
CREATE OR REPLACE VIEW auth.recent_permission_audit_log AS
SELECT
    pal.id,
    pal.action,
    pal.resource_type,
    pal.resource_id,
    u.name as user_name,
    u.email as user_email,
    performer.name as performed_by_name,
    pal.ip_address,
    pal.created_at
FROM auth.permission_audit_log pal
LEFT JOIN auth.users u ON pal.user_id = u.id
LEFT JOIN auth.users performer ON pal.performed_by = performer.id
ORDER BY pal.created_at DESC
LIMIT 100;

COMMIT;
