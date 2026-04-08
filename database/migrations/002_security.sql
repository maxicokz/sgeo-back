-- Migration 002: Security tables
-- Created: 2026-04-08
-- Description: Audit logs, login attempts, user sessions, MFA secrets

-- ============================================================
-- Расширенная таблица аудита
-- ============================================================
CREATE TABLE IF NOT EXISTS audit_logs (
    id         BIGSERIAL PRIMARY KEY,
    user_id    INTEGER,
    action     VARCHAR(100)  NOT NULL,
    ip         VARCHAR(45),
    user_agent VARCHAR(500),
    context    JSONB,
    created_at TIMESTAMP DEFAULT NOW()
);

-- ============================================================
-- Попытки входа (для защиты от брутфорса)
-- ============================================================
CREATE TABLE IF NOT EXISTS login_attempts (
    id           BIGSERIAL PRIMARY KEY,
    username     VARCHAR(255),
    ip           VARCHAR(45),
    attempted_at TIMESTAMP DEFAULT NOW(),
    success      BOOLEAN DEFAULT FALSE
);

-- ============================================================
-- Сессии пользователей (токен-based)
-- ============================================================
CREATE TABLE IF NOT EXISTS user_sessions (
    id            BIGSERIAL PRIMARY KEY,
    user_id       INTEGER   NOT NULL,
    token         VARCHAR(64) UNIQUE NOT NULL,
    ip            VARCHAR(45),
    user_agent    VARCHAR(500),
    last_activity TIMESTAMP DEFAULT NOW(),
    expires_at    TIMESTAMP NOT NULL,
    created_at    TIMESTAMP DEFAULT NOW()
);

-- ============================================================
-- MFA секреты (TOTP)
-- ============================================================
CREATE TABLE IF NOT EXISTS mfa_secrets (
    id           BIGSERIAL PRIMARY KEY,
    user_id      INTEGER UNIQUE NOT NULL,
    secret       VARCHAR(32)  NOT NULL,
    enabled      BOOLEAN DEFAULT FALSE,
    backup_codes JSONB,
    created_at   TIMESTAMP DEFAULT NOW()
);

-- ============================================================
-- Индексы
-- ============================================================
CREATE INDEX IF NOT EXISTS idx_audit_logs_user_id    ON audit_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_audit_logs_created_at ON audit_logs(created_at);
CREATE INDEX IF NOT EXISTS idx_login_attempts_ip       ON login_attempts(ip, attempted_at);
CREATE INDEX IF NOT EXISTS idx_login_attempts_username ON login_attempts(username, attempted_at);
CREATE INDEX IF NOT EXISTS idx_user_sessions_token     ON user_sessions(token);
