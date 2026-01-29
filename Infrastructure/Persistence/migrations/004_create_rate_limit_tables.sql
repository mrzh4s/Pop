-- Rate Limiting Database Schema
-- Creates tables for tracking rate limit attempts

BEGIN TRANSACTION;

-- Attach main database
ATTACH DATABASE 'database/app.db' AS main;

-- Rate limit attempts table
-- Tracks all rate limit attempts with expiration
CREATE TABLE IF NOT EXISTS main.rate_limit_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    key VARCHAR(255) NOT NULL,
    attempts INTEGER NOT NULL DEFAULT 1,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_rate_limit_key ON main.rate_limit_attempts(key);
CREATE INDEX IF NOT EXISTS idx_rate_limit_expires ON main.rate_limit_attempts(expires_at);
CREATE INDEX IF NOT EXISTS idx_rate_limit_key_expires ON main.rate_limit_attempts(key, expires_at);

-- Trigger to automatically clean up expired records
-- Runs after each insert to keep the table clean
CREATE TRIGGER IF NOT EXISTS cleanup_expired_rate_limits
AFTER INSERT ON main.rate_limit_attempts
BEGIN
    DELETE FROM main.rate_limit_attempts
    WHERE expires_at < datetime('now');
END;

-- Trigger to update the updated_at timestamp
CREATE TRIGGER IF NOT EXISTS update_rate_limit_timestamp
AFTER UPDATE ON main.rate_limit_attempts
BEGIN
    UPDATE main.rate_limit_attempts
    SET updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.id;
END;

COMMIT;
