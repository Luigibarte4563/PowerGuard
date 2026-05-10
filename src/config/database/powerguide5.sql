CREATE DATABASE powerguide;
USE powerguide;

-- USERS
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    google_id VARCHAR(100) UNIQUE NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NULL,
    picture TEXT NULL,
    auth_provider ENUM('local','google') NOT NULL DEFAULT 'local',
    role ENUM('user','electric_company') DEFAULT 'user',
    account_status ENUM('active','suspended','banned') DEFAULT 'active',
    is_verified BOOLEAN DEFAULT FALSE,
    refresh_token TEXT NULL,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    location_name VARCHAR(255) NULL,
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL
);

CREATE INDEX idx_user_location ON users(latitude, longitude);

-- ELECTRIC COMPANIES
CREATE TABLE electric_companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    company_name VARCHAR(255) NOT NULL,
    company_email VARCHAR(150) NULL,
    contact_number VARCHAR(50) NULL,
    address TEXT NULL,
    logo TEXT NULL,
    verification_status ENUM('pending','verified','rejected') DEFAULT 'pending',
    company_status ENUM('active','suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- MAINTENANCE
CREATE TABLE maintenance_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    electric_company_id INT NOT NULL,

    affected_area VARCHAR(255),
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,

    affected_barangays JSON NOT NULL,
    radius INT DEFAULT 2000,

    maintenance_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,

    description TEXT,

    estimated_restoration_time DATETIME NULL,

    status ENUM('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (electric_company_id)
        REFERENCES electric_companies(id)
        ON DELETE CASCADE
);

-- OUTAGE REPORTS
CREATE TABLE outage_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    report_key VARCHAR(255) NOT NULL,
    location_name VARCHAR(255) NOT NULL,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    category ENUM(
        'power_outage','low_voltage','power_fluctuation',
        'transformer_explosion','fallen_power_line',
        'electrical_fire','scheduled_maintenance','unknown_issue'
    ) DEFAULT 'power_outage',
    severity ENUM('minor','moderate','critical') DEFAULT 'moderate',
    description TEXT,
    image_proof TEXT NULL,
    affected_houses INT DEFAULT 1,
    is_active BOOLEAN DEFAULT TRUE,
    hazard_type ENUM('none','smoke','sparks','fire','fallen_wire','explosion_sound') DEFAULT 'none',
    status ENUM('active','under_review','verified','resolved','rejected') DEFAULT 'active',
    started_at DATETIME NULL,
    verified_by_company_id INT NULL,
    resolved_by_company_id INT NULL,
    verified_at DATETIME NULL,
    resolved_at DATETIME NULL,
    resolution_note TEXT NULL,
    maintenance_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by_company_id) REFERENCES electric_companies(id) ON DELETE SET NULL,
    FOREIGN KEY (resolved_by_company_id) REFERENCES electric_companies(id) ON DELETE SET NULL,
    FOREIGN KEY (maintenance_id) REFERENCES maintenance_schedules(id) ON DELETE SET NULL
);

CREATE TABLE power_stations (
    id INT AUTO_INCREMENT PRIMARY KEY,

    created_by INT NOT NULL,

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

    access_type ENUM('free','paid') DEFAULT 'free',

    availability_status ENUM(
        'available',
        'busy',
        'offline',
        'maintenance'
    ) DEFAULT 'available',

    operating_hours VARCHAR(100),
    charging_type VARCHAR(100),

    description TEXT,
    image TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- INDEXES
CREATE INDEX idx_station_type ON power_stations(station_type);
CREATE INDEX idx_availability ON power_stations(availability_status);
CREATE INDEX idx_station_location ON power_stations(latitude, longitude);
CREATE INDEX idx_created_by ON power_stations(created_by);
-- NOTIFICATIONS (API HANDLED)

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    title VARCHAR(255),
    message TEXT,

    type ENUM('maintenance','outage','emergency','system') DEFAULT 'maintenance',

    is_read BOOLEAN DEFAULT FALSE,

    maintenance_id INT NULL,

    source_type ENUM('maintenance','outage','system') NULL,

    location VARCHAR(255) NULL,  -- ✅ added location

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

    FOREIGN KEY (maintenance_id)
        REFERENCES maintenance_schedules(id)
        ON DELETE CASCADE
);