-- =========================================================================
-- Digital Document Repository Platform
-- Database: digital_document_repository
-- Web Application Development using PHP
-- =========================================================================

-- NOTE: On shared hosting (e.g. byetCluster/InfinityFree) you cannot create
-- new databases - you only have access to the single database already
-- created for your account. The USE statement below points straight at it.

CREATE DATABASE IF NOT EXISTS `digital_document_repository` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `digital_document_repository`;

-- -------------------------------------------------------------------------
-- Table: users
-- Stores both normal users and admin (role column differentiates them)
-- -------------------------------------------------------------------------
CREATE TABLE users (
    user_id         INT AUTO_INCREMENT PRIMARY KEY,
    full_name       VARCHAR(100)        NOT NULL,
    email           VARCHAR(150)        NOT NULL UNIQUE,
    username        VARCHAR(50)         NOT NULL UNIQUE,
    password        VARCHAR(255)        NOT NULL,
    role            ENUM('admin','user') NOT NULL DEFAULT 'user',
    profile_picture VARCHAR(255)        DEFAULT 'assets/images/default-avatar.png',
    phone           VARCHAR(20)         DEFAULT NULL,
    status          ENUM('active','blocked') NOT NULL DEFAULT 'active',
    reset_token     VARCHAR(255)        DEFAULT NULL,
    reset_expiry    DATETIME            DEFAULT NULL,
    created_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------
-- Table: categories
-- Document categories, created by admin or users
-- -------------------------------------------------------------------------
CREATE TABLE categories (
    category_id     INT AUTO_INCREMENT PRIMARY KEY,
    category_name   VARCHAR(100) NOT NULL UNIQUE,
    description     VARCHAR(255) DEFAULT NULL,
    created_by      INT          NOT NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_category_creator FOREIGN KEY (created_by)
        REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------
-- Table: documents
-- Core document metadata
-- -------------------------------------------------------------------------
CREATE TABLE documents (
    document_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT          NOT NULL,
    category_id     INT          DEFAULT NULL,
    document_name   VARCHAR(150) NOT NULL,
    description     TEXT         DEFAULT NULL,
    file_name       VARCHAR(255) NOT NULL,      -- stored (unique) file name on disk
    file_path       VARCHAR(255) NOT NULL,      -- relative path inside uploads/
    file_type       VARCHAR(10)  NOT NULL,      -- pdf, docx, ppt, jpg, png
    file_size       BIGINT       NOT NULL,      -- size in bytes
    version_no      DECIMAL(4,1) NOT NULL DEFAULT 1.0,
    status          ENUM('active','deleted') NOT NULL DEFAULT 'active',
    uploaded_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_document_user FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_document_category FOREIGN KEY (category_id)
        REFERENCES categories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------
-- Table: uploads
-- Keeps a version history log every time a document is uploaded / re-uploaded
-- -------------------------------------------------------------------------
CREATE TABLE uploads (
    upload_id       INT AUTO_INCREMENT PRIMARY KEY,
    document_id     INT          NOT NULL,
    uploaded_by     INT          NOT NULL,
    file_name       VARCHAR(255) NOT NULL,
    file_path       VARCHAR(255) NOT NULL,
    version_no      DECIMAL(4,1) NOT NULL,
    file_size       BIGINT       NOT NULL,
    uploaded_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_upload_document FOREIGN KEY (document_id)
        REFERENCES documents(document_id) ON DELETE CASCADE,
    CONSTRAINT fk_upload_user FOREIGN KEY (uploaded_by)
        REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------
-- Table: activity_logs
-- Tracks user/admin actions for reporting & analytics
-- -------------------------------------------------------------------------
CREATE TABLE activity_logs (
    log_id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT          NOT NULL,
    action           VARCHAR(255) NOT NULL,     -- e.g. "Uploaded Document", "Deleted Category"
    module          VARCHAR(50)  NOT NULL,      -- e.g. "Document", "Category", "Auth"
    ip_address      VARCHAR(45)  DEFAULT NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_user FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------
-- Indexes for faster search
-- -------------------------------------------------------------------------
CREATE INDEX idx_document_name ON documents(document_name);
CREATE INDEX idx_document_date ON documents(uploaded_at);
CREATE INDEX idx_document_category ON documents(category_id);
CREATE INDEX idx_document_user ON documents(user_id);

-- -------------------------------------------------------------------------
-- Default Admin Account
-- Username: admin | Password: Admin@123  (hashed using PHP password_hash - bcrypt)
-- -------------------------------------------------------------------------
INSERT INTO users (full_name, email, username, password, role)
VALUES (
    'System Administrator',
    'admin@ddrp.com',
    'admin',
    '$2y$10$xnTQaBsCy44Gjg7UYndQu.c3JFqVrc1dBka66wdh5UsEJs0tLjoBi', -- Admin@123
    'admin'
);

-- -------------------------------------------------------------------------
-- Sample Categories
-- -------------------------------------------------------------------------
INSERT INTO categories (category_name, description, created_by) VALUES
('Academic', 'Academic notes, syllabus and study material', 1),
('Projects', 'Project reports and source documents', 1),
('Certificates', 'Certificates and achievements', 1),
('Personal', 'Personal documents', 1),
('Others', 'Miscellaneous documents', 1);
