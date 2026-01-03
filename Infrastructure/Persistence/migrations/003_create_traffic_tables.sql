-- ============================================================
-- API Traffic Monitoring System - Database Schema (SQLite)
-- File: apps/database/migrations/003_create_traffic_tables.sql
--
-- Uses ATTACH DATABASE to create traffic schema in SQLite
-- Makes SQLite code compatible with PostgreSQL schema notation
-- ============================================================

BEGIN TRANSACTION;

-- Create traffic database file if it doesn't exist
-- ATTACH it as 'traffic' schema
ATTACH DATABASE 'database/traffic.db' AS traffic;

-- Drop tables if existing (to allow re-run)
DROP TABLE IF EXISTS traffic.api_traffic;

-- ============== API TRAFFIC TABLE ==============
-- Stores all API request/response traffic for monitoring
CREATE TABLE IF NOT EXISTS traffic.api_traffic
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    traffic VARCHAR(20) NOT NULL,        -- 'inbound' or 'outbound'
    url TEXT NOT NULL,                   -- API endpoint URL
    method VARCHAR(10),                  -- HTTP method (GET, POST, PUT, DELETE, etc.)
    headers TEXT,                        -- Request headers (JSON)
    body TEXT,                           -- Request body
    response TEXT,                       -- Response body
    status VARCHAR(20),                  -- 'success', 'error', 'failed', 'pending'
    status_code INTEGER,                 -- HTTP status code (200, 404, 500, etc.)
    response_time INTEGER,               -- Response time in milliseconds
    error_message TEXT,                  -- Error details if any
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_api_traffic_traffic ON traffic.api_traffic(traffic);
CREATE INDEX IF NOT EXISTS idx_api_traffic_method ON traffic.api_traffic(method);
CREATE INDEX IF NOT EXISTS idx_api_traffic_status ON traffic.api_traffic(status);
CREATE INDEX IF NOT EXISTS idx_api_traffic_created ON traffic.api_traffic(created_at);
CREATE INDEX IF NOT EXISTS idx_api_traffic_url ON traffic.api_traffic(url);
CREATE INDEX IF NOT EXISTS idx_api_traffic_status_code ON traffic.api_traffic(status_code);

-- Composite indexes for common queries
CREATE INDEX IF NOT EXISTS idx_api_traffic_traffic_status ON traffic.api_traffic(traffic, status);
CREATE INDEX IF NOT EXISTS idx_api_traffic_method_status ON traffic.api_traffic(method, status);

-- ============== TRAFFIC STATISTICS VIEW ==============
-- View for quick traffic statistics
CREATE VIEW IF NOT EXISTS traffic.traffic_statistics AS
SELECT
    traffic,
    method,
    status,
    COUNT(*) as request_count,
    AVG(response_time) as avg_response_time,
    MIN(response_time) as min_response_time,
    MAX(response_time) as max_response_time,
    SUM(CASE WHEN status IN ('error', 'failed') THEN 1 ELSE 0 END) as error_count,
    ROUND(
        CAST(SUM(CASE WHEN status IN ('error', 'failed') THEN 1 ELSE 0 END) AS REAL) /
        CAST(COUNT(*) AS REAL) * 100,
        2
    ) as error_rate,
    DATE(created_at) as date
FROM traffic.api_traffic
GROUP BY traffic, method, status, DATE(created_at);

-- ============== ENDPOINT PERFORMANCE VIEW ==============
-- View for endpoint performance metrics
CREATE VIEW IF NOT EXISTS traffic.endpoint_performance AS
SELECT
    url,
    method,
    COUNT(*) as total_requests,
    AVG(response_time) as avg_response_time,
    MIN(response_time) as min_response_time,
    MAX(response_time) as max_response_time,
    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count,
    SUM(CASE WHEN status IN ('error', 'failed') THEN 1 ELSE 0 END) as error_count,
    ROUND(
        CAST(SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) AS REAL) /
        CAST(COUNT(*) AS REAL) * 100,
        2
    ) as success_rate
FROM traffic.api_traffic
WHERE created_at >= datetime('now', '-7 days')
GROUP BY url, method
ORDER BY total_requests DESC;

-- ============== HOURLY TRAFFIC VIEW ==============
-- View for hourly traffic patterns
CREATE VIEW IF NOT EXISTS traffic.hourly_traffic AS
SELECT
    strftime('%Y-%m-%d %H:00:00', created_at) as hour,
    traffic,
    COUNT(*) as request_count,
    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count,
    SUM(CASE WHEN status IN ('error', 'failed') THEN 1 ELSE 0 END) as error_count
FROM traffic.api_traffic
WHERE created_at >= datetime('now', '-7 days')
GROUP BY strftime('%Y-%m-%d %H:00:00', created_at), traffic
ORDER BY hour DESC;

-- ============== ERROR LOG VIEW ==============
-- View for recent errors with details
CREATE VIEW IF NOT EXISTS traffic.recent_errors AS
SELECT
    id,
    traffic,
    url,
    method,
    status,
    status_code,
    error_message,
    created_at
FROM traffic.api_traffic
WHERE status IN ('error', 'failed', 'false')
ORDER BY created_at DESC;

COMMIT;
