-- ============================================================
-- Authentication & Authorization System Schema (SQLite)
-- File: apps/database/migrations/000_create_auth_tables.sql
--
-- Uses ATTACH DATABASE to create auth schema in SQLite
-- Makes SQLite code compatible with PostgreSQL schema notation
-- ============================================================

BEGIN TRANSACTION;

-- Create auth database file if it doesn't exist
-- ATTACH it as 'auth' schema
ATTACH DATABASE 'database/auth.db' AS auth;

-- Drop tables if existing (to allow re-run)
DROP TABLE IF EXISTS auth.verification_codes;
DROP TABLE IF EXISTS auth.user_details;
DROP TABLE IF EXISTS auth.sessions;
DROP TABLE IF EXISTS auth.password_resets;
DROP TABLE IF EXISTS auth.login_attempts;
DROP TABLE IF EXISTS auth.group_user;
DROP TABLE IF EXISTS auth.role_user;
DROP TABLE IF EXISTS auth.groups;
DROP TABLE IF EXISTS auth.users;

-- ============== USERS TABLE ==============
-- Core user accounts table
CREATE TABLE IF NOT EXISTS auth.users
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email_verified_at DATETIME,
    is_active INTEGER NOT NULL DEFAULT 1,
    last_login_at DATETIME,
    remember_token VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_users_email ON auth.users(email);
CREATE INDEX IF NOT EXISTS idx_users_is_active ON auth.users(is_active);
CREATE INDEX IF NOT EXISTS idx_users_created_at ON auth.users(created_at);

-- ============== USER DETAILS TABLE ==============
-- Extended user information
CREATE TABLE IF NOT EXISTS auth.user_details
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL UNIQUE,
    first_name VARCHAR(255),
    last_name VARCHAR(255),
    phone VARCHAR(255),
    date_of_birth DATE,
    gender VARCHAR(255),
    unit_no TEXT,
    street_name TEXT,
    city VARCHAR(255),
    state VARCHAR(255),
    postcode VARCHAR(255),
    country VARCHAR(255),
    employee_id VARCHAR(255),
    telegram_id INTEGER,
    bio TEXT,
    profile_picture VARCHAR(255),
    preferences TEXT,  -- JSON stored as TEXT
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_user_details_user_id ON auth.user_details(user_id);
CREATE INDEX IF NOT EXISTS idx_user_details_employee_id ON auth.user_details(employee_id);

-- ============== GROUPS TABLE ==============
-- User groups for organizational structure
CREATE TABLE IF NOT EXISTS auth.groups
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE,
    display_name VARCHAR(255) NOT NULL,
    description TEXT,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_groups_name ON auth.groups(name);
CREATE INDEX IF NOT EXISTS idx_groups_is_active ON auth.groups(is_active);

-- ============== ROLE_USER TABLE ==============
-- Many-to-many relationship between users and roles
-- Note: roles table is created in 001_create_permissions_tables.sql
CREATE TABLE IF NOT EXISTS auth.role_user
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    role_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE CASCADE,
    UNIQUE (user_id, role_id)
);

CREATE INDEX IF NOT EXISTS idx_role_user_user_id ON auth.role_user(user_id);
CREATE INDEX IF NOT EXISTS idx_role_user_role_id ON auth.role_user(role_id);

-- ============== GROUP_USER TABLE ==============
-- Many-to-many relationship between users and groups
CREATE TABLE IF NOT EXISTS auth.group_user
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    group_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES auth.groups(id) ON DELETE CASCADE,
    UNIQUE (user_id, group_id)
);

CREATE INDEX IF NOT EXISTS idx_group_user_user_id ON auth.group_user(user_id);
CREATE INDEX IF NOT EXISTS idx_group_user_group_id ON auth.group_user(group_id);

-- ============== LOGIN ATTEMPTS TABLE ==============
-- Track failed login attempts for security
CREATE TABLE IF NOT EXISTS auth.login_attempts
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_address VARCHAR(45),
    email VARCHAR(255),
    attempts INTEGER NOT NULL DEFAULT 1,
    last_attempt DATETIME DEFAULT CURRENT_TIMESTAMP,
    blocked_until DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (ip_address, email)
);

CREATE INDEX IF NOT EXISTS idx_login_attempts_ip ON auth.login_attempts(ip_address);
CREATE INDEX IF NOT EXISTS idx_login_attempts_email ON auth.login_attempts(email);
CREATE INDEX IF NOT EXISTS idx_login_attempts_blocked_until ON auth.login_attempts(blocked_until);

-- ============== PASSWORD RESETS TABLE ==============
-- Password reset tokens
CREATE TABLE IF NOT EXISTS auth.password_resets
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_password_resets_email ON auth.password_resets(email);
CREATE INDEX IF NOT EXISTS idx_password_resets_token ON auth.password_resets(token);
CREATE INDEX IF NOT EXISTS idx_password_resets_expires_at ON auth.password_resets(expires_at);

-- ============== SESSIONS TABLE ==============
-- User sessions with device tracking
CREATE TABLE IF NOT EXISTS auth.sessions
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id TEXT NOT NULL UNIQUE,
    user_id INTEGER,
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload TEXT NOT NULL,  -- JSON stored as TEXT
    last_activity INTEGER NOT NULL,
    expires_at DATETIME,
    device_type VARCHAR(255),
    device_name VARCHAR(255),
    platform VARCHAR(255),
    browser VARCHAR(255),
    city VARCHAR(255),
    country VARCHAR(255),
    is_current INTEGER NOT NULL DEFAULT 0,
    is_trusted INTEGER NOT NULL DEFAULT 0,
    last_used_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_sessions_session_id ON auth.sessions(session_id);
CREATE INDEX IF NOT EXISTS idx_sessions_user_id ON auth.sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_sessions_last_activity ON auth.sessions(last_activity);
CREATE INDEX IF NOT EXISTS idx_sessions_expires_at ON auth.sessions(expires_at);

-- ============== VERIFICATION CODES TABLE ==============
-- Email/phone verification codes
CREATE TABLE IF NOT EXISTS auth.verification_codes
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER UNIQUE,
    code VARCHAR(10),
    expires_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_verification_codes_user_id ON auth.verification_codes(user_id);
CREATE INDEX IF NOT EXISTS idx_verification_codes_code ON auth.verification_codes(code);
CREATE INDEX IF NOT EXISTS idx_verification_codes_expires_at ON auth.verification_codes(expires_at);

-- ============== INSERT DEFAULT DATA ==============

-- Insert default groups
INSERT INTO auth.groups (name, display_name, description, is_active) VALUES
('admin', 'Admin Group', 'Administrative staff including HR, Finance, IT, and Management', 1),
('client', 'Client Group', 'Applicants and clients', 1),
('authority', 'Authority Group', 'Government officials and authorities', 1),
('vendor', 'Vendor Group', 'External vendors and suppliers', 1),
('board', 'Board Members', 'Board of directors and executives', 1);

-- Insert default admin user
-- Password: 'password' hashed with bcrypt
INSERT INTO auth.users (name, email, password, email_verified_at, is_active, created_at) VALUES
('System Administrator', 'admin@system.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', datetime('now'), 1, datetime('now'));

-- Insert admin user details
INSERT INTO auth.user_details (user_id, first_name, last_name, employee_id, created_at) VALUES
(1, 'System', 'Administrator', 'SYS001', datetime('now'));

-- ============== HELPER VIEWS ==============

-- User summary view with roles and groups
CREATE VIEW IF NOT EXISTS auth.user_summary AS
SELECT
    u.id,
    u.name,
    u.email,
    u.is_active,
    u.last_login_at,
    ud.employee_id,
    ud.first_name,
    ud.last_name,
    ud.phone,
    GROUP_CONCAT(DISTINCT g.display_name, ', ') as groups,
    u.created_at
FROM auth.users u
LEFT JOIN auth.user_details ud ON u.id = ud.user_id
LEFT JOIN auth.group_user gu ON u.id = gu.user_id
LEFT JOIN auth.groups g ON gu.group_id = g.id AND g.is_active = 1
WHERE u.is_active = 1
GROUP BY u.id, u.name, u.email, u.is_active, u.last_login_at,
         ud.employee_id, ud.first_name, ud.last_name, ud.phone, u.created_at
ORDER BY u.created_at;

-- Group composition view
CREATE VIEW IF NOT EXISTS auth.group_composition AS
SELECT
    g.name as group_name,
    g.display_name as group_display,
    COUNT(gu.user_id) as user_count,
    GROUP_CONCAT(u.name, ', ') as members
FROM auth.groups g
LEFT JOIN auth.group_user gu ON g.id = gu.group_id
LEFT JOIN auth.users u ON gu.user_id = u.id AND u.is_active = 1
WHERE g.is_active = 1
GROUP BY g.id, g.name, g.display_name
ORDER BY user_count DESC, g.name;

-- Active sessions view
CREATE VIEW IF NOT EXISTS auth.active_sessions AS
SELECT
    s.id,
    s.session_id,
    u.name as user_name,
    u.email,
    s.ip_address,
    s.device_type,
    s.platform,
    s.browser,
    s.city,
    s.country,
    s.is_current,
    s.is_trusted,
    s.last_used_at,
    datetime(s.last_activity, 'unixepoch') as last_activity_time
FROM auth.sessions s
LEFT JOIN auth.users u ON s.user_id = u.id
WHERE s.expires_at IS NULL OR s.expires_at > datetime('now')
ORDER BY s.last_activity DESC;

-- User login statistics
CREATE VIEW IF NOT EXISTS auth.user_login_stats AS
SELECT
    u.id as user_id,
    u.name,
    u.email,
    u.last_login_at,
    COUNT(DISTINCT s.id) as active_session_count,
    la.attempts as failed_login_attempts,
    la.blocked_until as login_blocked_until
FROM auth.users u
LEFT JOIN auth.sessions s ON u.id = s.user_id
    AND (s.expires_at IS NULL OR s.expires_at > datetime('now'))
LEFT JOIN auth.login_attempts la ON u.email = la.email
WHERE u.is_active = 1
GROUP BY u.id, u.name, u.email, u.last_login_at, la.attempts, la.blocked_until
ORDER BY u.last_login_at DESC;

-- Security alerts view
CREATE VIEW IF NOT EXISTS auth.security_alerts AS
SELECT
    'blocked_login' as alert_type,
    email as subject,
    'Account temporarily blocked due to failed login attempts' as message,
    blocked_until as alert_until,
    last_attempt as created_at
FROM auth.login_attempts
WHERE blocked_until > datetime('now')

UNION ALL

SELECT
    'expired_reset_token' as alert_type,
    email as subject,
    'Unused password reset token expired' as message,
    expires_at as alert_until,
    created_at
FROM auth.password_resets
WHERE expires_at < datetime('now')
    AND created_at > datetime('now', '-7 days')

ORDER BY created_at DESC;

COMMIT;
