CREATE DATABASE powerguide;
USE powerguide;

-- =====================================
-- USERS (WITH LOCATION)
-- =====================================
CREATE TABLE users (

    id INT AUTO_INCREMENT PRIMARY KEY,

    google_id VARCHAR(100) UNIQUE NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NULL,
    picture TEXT NULL,

    auth_provider ENUM('local','google') NOT NULL DEFAULT 'local',

    role ENUM('user','electric_company','admin') DEFAULT 'user',

    account_status ENUM('active','suspended','banned') DEFAULT 'active',

    is_verified BOOLEAN DEFAULT FALSE,

    refresh_token TEXT NULL,

    last_login TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        ON UPDATE CURRENT_TIMESTAMP,

    -- LOCATION
    location_name VARCHAR(255) NULL,
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL
);

CREATE INDEX idx_user_location
ON users(latitude, longitude);

-- =====================================
-- ELECTRIC COMPANIES
-- =====================================
CREATE TABLE electric_companies (

    id INT AUTO_INCREMENT PRIMARY KEY,

    -- LINK TO USERS TABLE
    user_id INT NOT NULL UNIQUE,

    company_name VARCHAR(255) NOT NULL,

    company_email VARCHAR(150) NULL,

    contact_number VARCHAR(50) NULL,

    address TEXT NULL,

    logo TEXT NULL,

    verification_status ENUM(
        'pending',
        'verified',
        'rejected'
    ) DEFAULT 'pending',

    company_status ENUM(
        'active',
        'suspended'
    ) DEFAULT 'active',

    verified_by_admin_id INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    FOREIGN KEY (verified_by_admin_id)
        REFERENCES users(id)
        ON DELETE SET NULL
);

-- =====================================
-- COMPANY APPLICATIONS
-- =====================================
CREATE TABLE company_applications (

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT,

    company_name VARCHAR(255),

    business_document TEXT,

    application_status ENUM(
        'pending',
        'approved',
        'rejected'
    ) DEFAULT 'pending',

    admin_note TEXT NULL,

    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    reviewed_at TIMESTAMP NULL,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);

-- =====================================
-- MAINTENANCE SCHEDULES
-- =====================================
CREATE TABLE maintenance_schedules (

    id INT AUTO_INCREMENT PRIMARY KEY,

    -- references electric_companies.id
    electric_company_id INT NOT NULL,

    affected_area VARCHAR(255) NULL,

    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,

    affected_barangays JSON NOT NULL,

    -- searchable generated text
    barangays_text TEXT
        GENERATED ALWAYS AS (
            JSON_UNQUOTE(affected_barangays)
        ) STORED,

    radius INT DEFAULT 2000,

    maintenance_date DATE NOT NULL,

    start_time TIME NOT NULL,

    end_time TIME NOT NULL,

    description TEXT NULL,

    estimated_restoration_time DATETIME NULL,

    status ENUM(
        'upcoming',
        'ongoing',
        'completed',
        'cancelled'
    ) DEFAULT 'upcoming',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    -- FOREIGN KEY
    CONSTRAINT fk_maintenance_company
        FOREIGN KEY (electric_company_id)
        REFERENCES electric_companies(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- =========================
-- INDEXES
-- =========================

CREATE INDEX idx_maintenance_location
ON maintenance_schedules(latitude, longitude);

CREATE INDEX idx_maintenance_company
ON maintenance_schedules(electric_company_id);

CREATE INDEX idx_barangays_text
ON maintenance_schedules(barangays_text(255));

CREATE INDEX idx_maintenance_status
ON maintenance_schedules(status);

CREATE INDEX idx_maintenance_date
ON maintenance_schedules(maintenance_date);

-- =====================================
-- OUTAGE REPORTS
-- =====================================
CREATE TABLE outage_reports (

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    location_name VARCHAR(255) NOT NULL,

    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),

    category ENUM(
        'power_outage',
        'low_voltage',
        'power_fluctuation',
        'transformer_explosion',
        'fallen_power_line',
        'electrical_fire',
        'scheduled_maintenance',
        'unknown_issue'
    ) DEFAULT 'power_outage',

    severity ENUM(
        'minor',
        'moderate',
        'critical'
    ) DEFAULT 'moderate',

    description TEXT,

    image_proof TEXT NULL,

    affected_houses INT DEFAULT 1,

    is_active ENUM(
        'yes',
        'no',
        'unknown'
    ) DEFAULT 'yes',

    hazard_type ENUM(
        'none',
        'smoke',
        'sparks',
        'fire',
        'fallen_wire',
        'explosion_sound'
    ) DEFAULT 'none',

    started_at DATETIME NULL,

    status ENUM(
        'unverified',
        'under_review',
        'verified',
        'resolved',
        'fake_report'
    ) DEFAULT 'unverified',

    verified_by INT NULL,

    is_deleted TINYINT(1) DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    maintenance_id INT NULL,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    FOREIGN KEY (verified_by)
        REFERENCES users(id)
        ON DELETE SET NULL,

    FOREIGN KEY (maintenance_id)
        REFERENCES maintenance_schedules(id)
        ON DELETE SET NULL
);

CREATE INDEX idx_user_id
ON outage_reports(user_id);

CREATE INDEX idx_status
ON outage_reports(status);

CREATE INDEX idx_location
ON outage_reports(latitude, longitude);

CREATE INDEX idx_status_category
ON outage_reports(status, category);

-- =====================================
-- POWER STATIONS
-- =====================================
CREATE TABLE power_stations (

    id INT AUTO_INCREMENT PRIMARY KEY,

    created_by INT NULL,

    station_name VARCHAR(255) NOT NULL,

    location_name VARCHAR(255) NOT NULL,

    latitude DECIMAL(10,8),

    longitude DECIMAL(11,8),

    station_type ENUM(
        'power_station',
        'solar_station',
        'charging_station',
        'generator_station'
    ) NOT NULL,

    access_type ENUM(
        'free',
        'paid'
    ) DEFAULT 'free',

    availability_status ENUM(
        'available',
        'busy',
        'offline',
        'maintenance'
    ) DEFAULT 'available',

    operating_hours VARCHAR(100) NULL,

    charging_type VARCHAR(100) NULL,

    description TEXT,

    image TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by)
        REFERENCES users(id)
        ON DELETE SET NULL
);

CREATE INDEX idx_station_type
ON power_stations(station_type);

CREATE INDEX idx_availability
ON power_stations(availability_status);

CREATE INDEX idx_station_location
ON power_stations(latitude, longitude);

CREATE INDEX idx_created_by
ON power_stations(created_by);

-- =====================================
-- NOTIFICATIONS
-- =====================================
CREATE TABLE notifications (

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    title VARCHAR(255),

    message TEXT,

    type ENUM(
        'maintenance',
        'outage',
        'system'
    ) DEFAULT 'maintenance',

    is_read BOOLEAN DEFAULT FALSE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);

CREATE INDEX idx_notif_user
ON notifications(user_id);

CREATE INDEX idx_notif_read
ON notifications(is_read);

-- =====================================
-- ADMIN LOGS
-- =====================================
CREATE TABLE admin_logs (

    id INT AUTO_INCREMENT PRIMARY KEY,

    admin_id INT,

    action_type VARCHAR(255),

    action_details TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (admin_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);