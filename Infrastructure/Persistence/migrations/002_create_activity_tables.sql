-- ============================================================
-- Activity Logging System - Database Schema (SQLite)
-- File: apps/database/migrations/002_create_activity_tables.sql
--
-- Uses ATTACH DATABASE to create log schema in SQLite
-- Makes SQLite code compatible with PostgreSQL schema notation
-- ============================================================

BEGIN TRANSACTION;

-- Create log database file if it doesn't exist
-- ATTACH it as 'log' schema
ATTACH DATABASE 'database/log.db' AS log;

-- Drop tables if existing (to allow re-run)
DROP TABLE IF EXISTS log.project_activities;
DROP TABLE IF EXISTS log.user_activities;

-- ============== USER ACTIVITIES TABLE ==============
-- Stores user activity logs with location and device information
CREATE TABLE IF NOT EXISTS log.user_activities
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    ip_address VARCHAR(45),             -- IPv4 or IPv6 address
    url TEXT,                           -- Request URL
    location TEXT,                      -- JSON location data from IP geolocation
    device TEXT,                        -- User agent string
    message TEXT,                       -- Activity description
    action_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_user_activities_user ON log.user_activities(user_id);
CREATE INDEX IF NOT EXISTS idx_user_activities_action_at ON log.user_activities(action_at);
CREATE INDEX IF NOT EXISTS idx_user_activities_ip ON log.user_activities(ip_address);

-- ============== PROJECT ACTIVITIES TABLE ==============
-- Stores project activity and workflow changes
CREATE TABLE IF NOT EXISTS log.project_activities
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    system_id VARCHAR(50) NOT NULL,     -- Project/System identifier
    current_flow INTEGER,               -- Current workflow step
    flow_timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    username VARCHAR(100),              -- User who made the change
    details TEXT,                       -- Change description
    authority VARCHAR(50),              -- Authority ID (optional)
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_project_activities_system ON log.project_activities(system_id);
CREATE INDEX IF NOT EXISTS idx_project_activities_flow ON log.project_activities(current_flow);
CREATE INDEX IF NOT EXISTS idx_project_activities_timestamp ON log.project_activities(flow_timestamp);
CREATE INDEX IF NOT EXISTS idx_project_activities_username ON log.project_activities(username);

-- ============== ACTIVITY SUMMARY VIEW ==============
-- View for quick activity summary per user
CREATE VIEW IF NOT EXISTS log.user_activity_summary AS
SELECT
    user_id,
    COUNT(*) as total_activities,
    MAX(action_at) as last_activity,
    COUNT(DISTINCT DATE(action_at)) as active_days,
    COUNT(DISTINCT ip_address) as unique_ips
FROM log.user_activities
GROUP BY user_id;

-- ============== PROJECT ACTIVITY VIEW ==============
-- View for project activity timeline
CREATE VIEW IF NOT EXISTS log.project_activity_timeline AS
SELECT
    system_id,
    current_flow,
    username,
    details,
    authority,
    flow_timestamp,
    ROW_NUMBER() OVER (PARTITION BY system_id ORDER BY flow_timestamp DESC) as activity_rank
FROM log.project_activities
ORDER BY flow_timestamp DESC;

COMMIT;
