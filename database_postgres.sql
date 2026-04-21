-- Civic Connect PostgreSQL Schema (Supabase-compatible)
-- Use this for cloud deployment on Supabase/PostgreSQL.

-- Keep trigger helper idempotent
CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id BIGSERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    location VARCHAR(255),
    email_verified BOOLEAN DEFAULT FALSE,
    email_verified_at TIMESTAMPTZ NULL,
    otp_code VARCHAR(6),
    otp_expires_at TIMESTAMPTZ NULL,
    otp_attempts INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    role VARCHAR(20) NOT NULL DEFAULT 'citizen' CHECK (role IN ('citizen', 'staff', 'admin')),
    last_login TIMESTAMPTZ NULL,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_users_email ON users (email);
CREATE INDEX IF NOT EXISTS idx_users_created_at ON users (created_at);
CREATE INDEX IF NOT EXISTS idx_users_is_active ON users (is_active);
CREATE INDEX IF NOT EXISTS idx_users_role ON users (role);

DROP TRIGGER IF EXISTS trg_users_updated_at ON users;
CREATE TRIGGER trg_users_updated_at
BEFORE UPDATE ON users
FOR EACH ROW
EXECUTE FUNCTION set_updated_at();

-- Create issues table
CREATE TABLE IF NOT EXISTS issues (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(100) NOT NULL,
    assigned_to BIGINT NULL,
    location VARCHAR(255),
    latitude NUMERIC(10, 8),
    longitude NUMERIC(11, 8),
    status VARCHAR(20) NOT NULL DEFAULT 'pending_review' CHECK (status IN ('pending_review', 'in_progress', 'resolved')),
    priority VARCHAR(10) NOT NULL DEFAULT 'medium' CHECK (priority IN ('low', 'medium', 'high')),
    image_path VARCHAR(255),
    upvote_count INT DEFAULT 0,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMPTZ NULL,
    CONSTRAINT fk_issues_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_issues_assigned_to FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_issues_category ON issues (category);
CREATE INDEX IF NOT EXISTS idx_issues_assigned_to ON issues (assigned_to);
CREATE INDEX IF NOT EXISTS idx_issues_created_at ON issues (created_at);
CREATE INDEX IF NOT EXISTS idx_issues_user_id ON issues (user_id);
CREATE INDEX IF NOT EXISTS idx_issues_priority ON issues (priority);

DROP TRIGGER IF EXISTS trg_issues_updated_at ON issues;
CREATE TRIGGER trg_issues_updated_at
BEFORE UPDATE ON issues
FOR EACH ROW
EXECUTE FUNCTION set_updated_at();

-- Create upvotes table
CREATE TABLE IF NOT EXISTS upvotes (
    id BIGSERIAL PRIMARY KEY,
    issue_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_upvotes_issue_id FOREIGN KEY (issue_id) REFERENCES issues(id) ON DELETE CASCADE,
    CONSTRAINT fk_upvotes_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT unique_upvote UNIQUE (issue_id, user_id)
);

CREATE INDEX IF NOT EXISTS idx_upvotes_issue_id ON upvotes (issue_id);
CREATE INDEX IF NOT EXISTS idx_upvotes_user_id ON upvotes (user_id);
CREATE INDEX IF NOT EXISTS idx_upvotes_created_at ON upvotes (created_at);

-- Create audit_trail table
CREATE TABLE IF NOT EXISTS audit_trail (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id BIGINT,
    old_values JSONB,
    new_values JSONB,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_trail_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_audit_action ON audit_trail (action);
CREATE INDEX IF NOT EXISTS idx_audit_entity_type ON audit_trail (entity_type);
CREATE INDEX IF NOT EXISTS idx_audit_user_id ON audit_trail (user_id);
CREATE INDEX IF NOT EXISTS idx_audit_created_at ON audit_trail (created_at);
CREATE INDEX IF NOT EXISTS idx_audit_entity ON audit_trail (entity_type, entity_id);

-- Create issue_updates table
CREATE TABLE IF NOT EXISTS issue_updates (
    id BIGSERIAL PRIMARY KEY,
    issue_id BIGINT NOT NULL,
    user_id BIGINT,
    update_type VARCHAR(20) NOT NULL DEFAULT 'status_change' CHECK (update_type IN ('status_change', 'image_added', 'assigned')),
    content TEXT,
    old_status VARCHAR(50),
    new_status VARCHAR(50),
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_issue_updates_issue_id FOREIGN KEY (issue_id) REFERENCES issues(id) ON DELETE CASCADE,
    CONSTRAINT fk_issue_updates_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_issue_updates_issue_id ON issue_updates (issue_id);
CREATE INDEX IF NOT EXISTS idx_issue_updates_update_type ON issue_updates (update_type);
CREATE INDEX IF NOT EXISTS idx_issue_updates_created_at ON issue_updates (created_at);

-- Create sessions table
CREATE TABLE IF NOT EXISTS sessions (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMPTZ NOT NULL,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sessions_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_sessions_user_id ON sessions (user_id);
CREATE INDEX IF NOT EXISTS idx_sessions_expires_at ON sessions (expires_at);

-- Create password_resets table
CREATE TABLE IF NOT EXISTS password_resets (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMPTZ NOT NULL,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_password_resets_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_password_resets_user_id ON password_resets (user_id);
CREATE INDEX IF NOT EXISTS idx_password_resets_expires_at ON password_resets (expires_at);

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    issue_id BIGINT,
    type VARCHAR(20) NOT NULL DEFAULT 'status_change' CHECK (type IN ('status_change', 'upvote', 'comment', 'system')),
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_notifications_issue_id FOREIGN KEY (issue_id) REFERENCES issues(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications (user_id);
CREATE INDEX IF NOT EXISTS idx_notifications_is_read ON notifications (is_read);
CREATE INDEX IF NOT EXISTS idx_notifications_created_at ON notifications (created_at);
CREATE INDEX IF NOT EXISTS idx_notifications_type ON notifications (type);

-- Automatic issue assignment setup
CREATE TABLE IF NOT EXISTS category_staff_mapping (
    id BIGSERIAL PRIMARY KEY,
    category VARCHAR(100) NOT NULL UNIQUE,
    staff_id BIGINT NOT NULL,
    department_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_category_staff_mapping_staff_id FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_category_staff_mapping_category ON category_staff_mapping (category);
CREATE INDEX IF NOT EXISTS idx_category_staff_mapping_staff_id ON category_staff_mapping (staff_id);

DROP TRIGGER IF EXISTS trg_category_staff_mapping_updated_at ON category_staff_mapping;
CREATE TRIGGER trg_category_staff_mapping_updated_at
BEFORE UPDATE ON category_staff_mapping
FOR EACH ROW
EXECUTE FUNCTION set_updated_at();

-- Insert default staff accounts for each department
-- Password for all: "Devfusion3" (bcrypt hash with cost=12)
INSERT INTO users (email, password_hash, first_name, last_name, phone, role, is_active, email_verified)
VALUES ('pwd@gov.in', '$2y$12$xxjXeGp06gJCnkI6HlS0LenWcxloHZUUcLrVDz2f.9zVZMuw0xwwG', 'PWD', 'Dept', '9876543210', 'staff', TRUE, TRUE)
ON CONFLICT (email) DO NOTHING;

INSERT INTO users (email, password_hash, first_name, last_name, phone, role, is_active, email_verified)
VALUES ('electricity@gov.in', '$2y$12$xxjXeGp06gJCnkI6HlS0LenWcxloHZUUcLrVDz2f.9zVZMuw0xwwG', 'Electricity', 'Dept', '9876543211', 'staff', TRUE, TRUE)
ON CONFLICT (email) DO NOTHING;

INSERT INTO users (email, password_hash, first_name, last_name, phone, role, is_active, email_verified)
VALUES ('sanitation@gov.in', '$2y$12$xxjXeGp06gJCnkI6HlS0LenWcxloHZUUcLrVDz2f.9zVZMuw0xwwG', 'Sanitation', 'Dept', '9876543212', 'staff', TRUE, TRUE)
ON CONFLICT (email) DO NOTHING;

INSERT INTO users (email, password_hash, first_name, last_name, phone, role, is_active, email_verified)
VALUES ('water@gov.in', '$2y$12$xxjXeGp06gJCnkI6HlS0LenWcxloHZUUcLrVDz2f.9zVZMuw0xwwG', 'Water', 'Dept', '9876543213', 'staff', TRUE, TRUE)
ON CONFLICT (email) DO NOTHING;

INSERT INTO users (email, password_hash, first_name, last_name, phone, role, is_active, email_verified)
VALUES ('horticulture@gov.in', '$2y$12$xxjXeGp06gJCnkI6HlS0LenWcxloHZUUcLrVDz2f.9zVZMuw0xwwG', 'Horticulture', 'Dept', '9876543214', 'staff', TRUE, TRUE)
ON CONFLICT (email) DO NOTHING;

INSERT INTO users (email, password_hash, first_name, last_name, phone, role, is_active, email_verified)
VALUES ('police@gov.in', '$2y$12$xxjXeGp06gJCnkI6HlS0LenWcxloHZUUcLrVDz2f.9zVZMuw0xwwG', 'Police', 'Dept', '9876543215', 'staff', TRUE, TRUE)
ON CONFLICT (email) DO NOTHING;

INSERT INTO users (email, password_hash, first_name, last_name, phone, role, is_active, email_verified)
VALUES ('municipal@gov.in', '$2y$12$xxjXeGp06gJCnkI6HlS0LenWcxloHZUUcLrVDz2f.9zVZMuw0xwwG', 'Municipal', 'Dept', '9876543216', 'staff', TRUE, TRUE)
ON CONFLICT (email) DO NOTHING;

INSERT INTO users (email, password_hash, first_name, last_name, phone, role, is_active, email_verified)
VALUES ('helpline@gov.in', '$2y$12$xxjXeGp06gJCnkI6HlS0LenWcxloHZUUcLrVDz2f.9zVZMuw0xwwG', 'Helpline', 'Dept', '9876543217', 'staff', TRUE, TRUE)
ON CONFLICT (email) DO NOTHING;

-- Map categories to staff members for automatic assignment
INSERT INTO category_staff_mapping (category, staff_id, department_name)
SELECT 'roads', id, 'Public Works Department (PWD)' FROM users WHERE email = 'pwd@gov.in'
ON CONFLICT (category) DO NOTHING;

INSERT INTO category_staff_mapping (category, staff_id, department_name)
SELECT 'street_lights', id, 'Electricity Department' FROM users WHERE email = 'electricity@gov.in'
ON CONFLICT (category) DO NOTHING;

INSERT INTO category_staff_mapping (category, staff_id, department_name)
SELECT 'trash', id, 'Sanitation Department' FROM users WHERE email = 'sanitation@gov.in'
ON CONFLICT (category) DO NOTHING;

INSERT INTO category_staff_mapping (category, staff_id, department_name)
SELECT 'water_drainage', id, 'Water Supply Board' FROM users WHERE email = 'water@gov.in'
ON CONFLICT (category) DO NOTHING;

INSERT INTO category_staff_mapping (category, staff_id, department_name)
SELECT 'parks_recreation', id, 'Horticulture Department' FROM users WHERE email = 'horticulture@gov.in'
ON CONFLICT (category) DO NOTHING;

INSERT INTO category_staff_mapping (category, staff_id, department_name)
SELECT 'public_safety', id, 'Police Department' FROM users WHERE email = 'police@gov.in'
ON CONFLICT (category) DO NOTHING;

INSERT INTO category_staff_mapping (category, staff_id, department_name)
SELECT 'graffiti_vandalism', id, 'Municipal Body' FROM users WHERE email = 'municipal@gov.in'
ON CONFLICT (category) DO NOTHING;

INSERT INTO category_staff_mapping (category, staff_id, department_name)
SELECT 'noise', id, 'Police Department' FROM users WHERE email = 'police@gov.in'
ON CONFLICT (category) DO NOTHING;

INSERT INTO category_staff_mapping (category, staff_id, department_name)
SELECT 'other', id, 'Municipal Helpline' FROM users WHERE email = 'helpline@gov.in'
ON CONFLICT (category) DO NOTHING;
