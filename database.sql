-- Civic Connect Database Schema
-- Created for civic engagement and issue tracking platform

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    location VARCHAR(255),
    email_verified BOOLEAN DEFAULT 0,
    email_verified_at TIMESTAMP NULL,
    otp_code VARCHAR(6),
    otp_expires_at TIMESTAMP NULL,
    otp_attempts INT DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    role ENUM('citizen', 'staff', 'admin') DEFAULT 'citizen',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_created_at (created_at),
    INDEX idx_is_active (is_active),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create issues table
CREATE TABLE issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(100) NOT NULL,
    assigned_to INT NULL,
    location VARCHAR(255),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    status ENUM('pending_review', 'in_progress', 'resolved') DEFAULT 'pending_review',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    image_path VARCHAR(255),
    upvote_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_category (category),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_created_at (created_at),
    INDEX idx_user_id (user_id),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create upvotes table
CREATE TABLE upvotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (issue_id) REFERENCES issues(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_upvote (issue_id, user_id),
    INDEX idx_issue_id (issue_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create audit_trail table
CREATE TABLE audit_trail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_action (action),
    INDEX idx_entity_type (entity_type),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Create issue_updates table (for status changes)
CREATE TABLE issue_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_id INT NOT NULL,
    user_id INT,
    update_type ENUM('status_change', 'image_added', 'assigned') DEFAULT 'status_change',
    content TEXT,
    old_status VARCHAR(50),
    new_status VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (issue_id) REFERENCES issues(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_issue_id (issue_id),
    INDEX idx_update_type (update_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create sessions table (for user login sessions)
CREATE TABLE sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create password_resets table
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    issue_id INT,
    type ENUM('status_change', 'upvote', 'comment', 'system') DEFAULT 'status_change',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50),
    is_read BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (issue_id) REFERENCES issues(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- AUTOMATIC ISSUE ASSIGNMENT SETUP
-- ============================================================================

-- Create category_staff_mapping table
CREATE TABLE IF NOT EXISTS category_staff_mapping (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(100) NOT NULL UNIQUE,
    staff_id INT NOT NULL,
    department_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_category (category),
    INDEX idx_staff_id (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default staff accounts for each department
-- Password for all: "Devfusion3" (bcrypt hash with cost=12)

-- PWD (Public Works Department) - Roads
INSERT INTO users (email, password_hash, first_name, last_name, phone, role, is_active, email_verified)
VALUES ('pwd@gov.in', '$2y$12$xxjXeGp06gJCnkI6HlS0LenWcxloHZUUcLrVDz2f.9zVZMuw0xwwG', 'PWD', 'Dept', '9876543210', 'staff', 1, 1);

-- Electricity Department - Street Lights
INSERT INTO users (email, password_hash, first_name, last_name, phone, role, is_active, email_verified)
VALUES ('electricity@gov.in', '$2y$12$xxjXeGp06gJCnkI6HlS0LenWcxloHZUUcLrVDz2f.9zVZMuw0xwwG', 'Electricity', 'Dept', '9876543211', 'staff', 1, 1);

-- Sanitation Department - Trash/Solid Waste
INSERT INTO users (email, password_hash, first_name, last_name, phone, role, is_active, email_verified)
VALUES ('sanitation@gov.in', '$2y$12$xxjXeGp06gJCnkI6HlS0LenWcxloHZUUcLrVDz2f.9zVZMuw0xwwG', 'Sanitation', 'Dept', '9876543212', 'staff', 1, 1);

-- Water Supply Board - Water & Drainage
INSERT INTO users (email, password_hash, first_name, last_name, phone, role, is_active, email_verified)
VALUES ('water@gov.in', '$2y$12$xxjXeGp06gJCnkI6HlS0LenWcxloHZUUcLrVDz2f.9zVZMuw0xwwG', 'Water', 'Dept', '9876543213', 'staff', 1, 1);

-- Horticulture Department - Parks & Recreation
INSERT INTO users (email, password_hash, first_name, last_name, phone, role, is_active, email_verified)
VALUES ('horticulture@gov.in', '$2y$12$xxjXeGp06gJCnkI6HlS0LenWcxloHZUUcLrVDz2f.9zVZMuw0xwwG', 'Horticulture', 'Dept', '9876543214', 'staff', 1, 1);

-- Police Department - Public Safety & Noise
INSERT INTO users (email, password_hash, first_name, last_name, phone, role, is_active, email_verified)
VALUES ('police@gov.in', '$2y$12$xxjXeGp06gJCnkI6HlS0LenWcxloHZUUcLrVDz2f.9zVZMuw0xwwG', 'Police', 'Dept', '9876543215', 'staff', 1, 1);

-- Municipal Body - Graffiti & Vandalism
INSERT INTO users (email, password_hash, first_name, last_name, phone, role, is_active, email_verified)
VALUES ('municipal@gov.in', '$2y$12$xxjXeGp06gJCnkI6HlS0LenWcxloHZUUcLrVDz2f.9zVZMuw0xwwG', 'Municipal', 'Dept', '9876543216', 'staff', 1, 1);

-- Municipal Helpline - Other Issues
INSERT INTO users (email, password_hash, first_name, last_name, phone, role, is_active, email_verified)
VALUES ('helpline@gov.in', '$2y$12$xxjXeGp06gJCnkI6HlS0LenWcxloHZUUcLrVDz2f.9zVZMuw0xwwG', 'Helpline', 'Dept', '9876543217', 'staff', 1, 1);

-- Map categories to staff members for automatic assignment
-- Categories must match EXACTLY what the frontend sends (lowercase with underscores)

INSERT INTO category_staff_mapping (category, staff_id, department_name)
SELECT 'roads', id, 'Public Works Department (PWD)' FROM users WHERE email = 'pwd@gov.in';

INSERT INTO category_staff_mapping (category, staff_id, department_name)
SELECT 'street_lights', id, 'Electricity Department' FROM users WHERE email = 'electricity@gov.in';

INSERT INTO category_staff_mapping (category, staff_id, department_name)
SELECT 'trash', id, 'Sanitation Department' FROM users WHERE email = 'sanitation@gov.in';

INSERT INTO category_staff_mapping (category, staff_id, department_name)
SELECT 'water_drainage', id, 'Water Supply Board' FROM users WHERE email = 'water@gov.in';

INSERT INTO category_staff_mapping (category, staff_id, department_name)
SELECT 'parks_recreation', id, 'Horticulture Department' FROM users WHERE email = 'horticulture@gov.in';

INSERT INTO category_staff_mapping (category, staff_id, department_name)
SELECT 'public_safety', id, 'Police Department' FROM users WHERE email = 'police@gov.in';

INSERT INTO category_staff_mapping (category, staff_id, department_name)
SELECT 'graffiti_vandalism', id, 'Municipal Body' FROM users WHERE email = 'municipal@gov.in';

INSERT INTO category_staff_mapping (category, staff_id, department_name)
SELECT 'noise', id, 'Police Department' FROM users WHERE email = 'police@gov.in';

INSERT INTO category_staff_mapping (category, staff_id, department_name)
SELECT 'other', id, 'Municipal Helpline' FROM users WHERE email = 'helpline@gov.in';
