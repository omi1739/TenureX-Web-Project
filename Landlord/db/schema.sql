-- =============================================================
-- TenureX — Database Schema (MySQL 8 / MariaDB 10+)
-- Drop & recreate everything: convenient during development.
-- =============================================================

DROP DATABASE IF EXISTS tenurex;
CREATE DATABASE tenurex
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;
USE tenurex;


-- =============================================================
-- USERS — landlords, future tenants, staff
-- =============================================================
CREATE TABLE users (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role            ENUM('landlord','tenant','staff','admin') NOT NULL DEFAULT 'landlord',
  full_name       VARCHAR(120)  NOT NULL,
  email           VARCHAR(190)  NOT NULL UNIQUE,
  phone           VARCHAR(40),
  password_hash   VARCHAR(255)  NOT NULL,    -- store via password_hash()
  avatar_url      VARCHAR(500),
  is_active       BOOLEAN       NOT NULL DEFAULT TRUE,
  created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


-- =============================================================
-- PROPERTIES — owned buildings/assets
-- =============================================================
CREATE TABLE properties (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  owner_id        INT UNSIGNED NOT NULL,
  name            VARCHAR(150) NOT NULL,
  address_line    VARCHAR(255) NOT NULL,
  city            VARCHAR(100) NOT NULL,
  region          VARCHAR(100),
  country         VARCHAR(100),
  postcode        VARCHAR(20),
  cover_image_url VARCHAR(500),
  total_units     INT UNSIGNED NOT NULL DEFAULT 1,
  monthly_value   DECIMAL(12,2),
  status          ENUM('active','draft','archived') NOT NULL DEFAULT 'active',
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_properties_owner
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);


-- =============================================================
-- UNITS — rooms/apartments inside a property
-- =============================================================
CREATE TABLE units (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  property_id     INT UNSIGNED NOT NULL,
  label           VARCHAR(50) NOT NULL,        -- e.g. "Suite 402"
  unit_type       VARCHAR(50),                 -- "Penthouse", "Studio", "2BR"
  bedrooms        TINYINT UNSIGNED,
  bathrooms       TINYINT UNSIGNED,
  area_sqft       INT UNSIGNED,
  monthly_rent    DECIMAL(10,2) NOT NULL,
  is_occupied     BOOLEAN NOT NULL DEFAULT FALSE,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_units_property
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_property_unit (property_id, label)
);


-- =============================================================
-- AMENITIES — many-to-many w/ units
-- =============================================================
CREATE TABLE amenities (
  id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name   VARCHAR(80) NOT NULL UNIQUE,          -- "Private Parking"
  icon   VARCHAR(50)                            -- icon key for the UI
);

CREATE TABLE unit_amenities (
  unit_id      INT UNSIGNED NOT NULL,
  amenity_id   INT UNSIGNED NOT NULL,
  PRIMARY KEY (unit_id, amenity_id),
  CONSTRAINT fk_ua_unit    FOREIGN KEY (unit_id)    REFERENCES units(id)     ON DELETE CASCADE,
  CONSTRAINT fk_ua_amenity FOREIGN KEY (amenity_id) REFERENCES amenities(id) ON DELETE CASCADE
);


-- =============================================================
-- RENTAL_REQUESTS — incoming applications
-- =============================================================
CREATE TABLE rental_requests (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  unit_id           INT UNSIGNED NOT NULL,
  applicant_name    VARCHAR(120) NOT NULL,
  applicant_email   VARCHAR(190) NOT NULL,
  applicant_phone   VARCHAR(40),
  occupation        VARCHAR(120),
  profile_summary   TEXT,
  credit_score      SMALLINT UNSIGNED,
  income_ratio      DECIMAL(4,1),
  requested_move_in DATE,
  lease_months      TINYINT UNSIGNED,
  status            ENUM('pending','in_review','accepted','rejected') NOT NULL DEFAULT 'pending',
  applied_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  decided_at        DATETIME,

  CONSTRAINT fk_rr_unit
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
  INDEX idx_rr_status (status),
  INDEX idx_rr_unit   (unit_id)
);


-- =============================================================
-- LEASES — signed agreements
-- =============================================================
CREATE TABLE leases (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  unit_id          INT UNSIGNED NOT NULL,
  tenant_user_id   INT UNSIGNED NOT NULL,
  rental_request_id INT UNSIGNED,                 -- nullable: lease may exist independently
  start_date       DATE NOT NULL,
  end_date         DATE NOT NULL,
  monthly_rent     DECIMAL(10,2) NOT NULL,
  security_deposit DECIMAL(10,2),
  status           ENUM('active','ended','terminated') NOT NULL DEFAULT 'active',
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_leases_unit    FOREIGN KEY (unit_id)           REFERENCES units(id)             ON DELETE RESTRICT,
  CONSTRAINT fk_leases_tenant  FOREIGN KEY (tenant_user_id)    REFERENCES users(id)             ON DELETE RESTRICT,
  CONSTRAINT fk_leases_request FOREIGN KEY (rental_request_id) REFERENCES rental_requests(id)   ON DELETE SET NULL
);


-- =============================================================
-- PAYMENTS — rent ledger
-- =============================================================
CREATE TABLE payments (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lease_id      INT UNSIGNED NOT NULL,
  amount        DECIMAL(10,2) NOT NULL,
  due_date      DATE NOT NULL,
  paid_at       DATETIME,
  method        ENUM('bank_transfer','card','cash','other') DEFAULT 'bank_transfer',
  reference     VARCHAR(100),
  status        ENUM('upcoming','paid','overdue','failed') NOT NULL DEFAULT 'upcoming',

  CONSTRAINT fk_payments_lease
    FOREIGN KEY (lease_id) REFERENCES leases(id) ON DELETE CASCADE,
  INDEX idx_payments_lease  (lease_id),
  INDEX idx_payments_status (status)
);


-- =============================================================
-- MAINTENANCE_REQUESTS — issue tracker
-- =============================================================
CREATE TABLE maintenance_requests (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  property_id     INT UNSIGNED NOT NULL,
  unit_id         INT UNSIGNED,
  reported_by     INT UNSIGNED,                -- nullable: tenant may not yet exist as user
  reporter_name   VARCHAR(120),                -- denormalised for display
  reporter_email  VARCHAR(190),
  title           VARCHAR(200) NOT NULL,
  description     TEXT,
  category        ENUM('hvac','plumbing','electrical','appliance','structural','other') NOT NULL,
  priority        ENUM('low','medium','urgent') NOT NULL DEFAULT 'medium',
  status          ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  reported_on     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  resolved_on     DATETIME,

  CONSTRAINT fk_mr_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  CONSTRAINT fk_mr_unit     FOREIGN KEY (unit_id)     REFERENCES units(id)      ON DELETE SET NULL,
  CONSTRAINT fk_mr_reporter FOREIGN KEY (reported_by) REFERENCES users(id)      ON DELETE SET NULL,

  INDEX idx_mr_status   (status),
  INDEX idx_mr_priority (priority)
);


-- =============================================================
-- MAINTENANCE_PHOTOS — photos attached to maintenance requests
-- =============================================================
CREATE TABLE maintenance_photos (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_id  INT UNSIGNED NOT NULL,
  url         VARCHAR(500) NOT NULL,
  uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_mphotos_request
    FOREIGN KEY (request_id) REFERENCES maintenance_requests(id) ON DELETE CASCADE
);


-- =============================================================
-- TECHNICIANS — repair contractors / handymen
-- =============================================================
CREATE TABLE technicians (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(120) NOT NULL,
  email         VARCHAR(190),
  phone         VARCHAR(40),
  specialty     VARCHAR(80),
  hourly_rate   DECIMAL(8,2),
  is_on_call    BOOLEAN NOT NULL DEFAULT FALSE,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);


-- =============================================================
-- TECHNICIAN_REQUESTS — dispatch from a maintenance ticket
-- =============================================================
CREATE TABLE technician_requests (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  maintenance_id    INT UNSIGNED NOT NULL,
  technician_id     INT UNSIGNED,
  scheduled_for     DATETIME,
  notes             TEXT,
  status            ENUM('pending','dispatched','completed','cancelled') NOT NULL DEFAULT 'pending',
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_tr_maintenance FOREIGN KEY (maintenance_id) REFERENCES maintenance_requests(id) ON DELETE CASCADE,
  CONSTRAINT fk_tr_technician  FOREIGN KEY (technician_id)  REFERENCES technicians(id)         ON DELETE SET NULL
);


-- =============================================================
-- MESSAGES — chat threads
-- =============================================================
CREATE TABLE message_threads (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subject      VARCHAR(200),
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_message_at DATETIME
);

CREATE TABLE message_thread_participants (
  thread_id  INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  PRIMARY KEY (thread_id, user_id),
  CONSTRAINT fk_mtp_thread FOREIGN KEY (thread_id) REFERENCES message_threads(id) ON DELETE CASCADE,
  CONSTRAINT fk_mtp_user   FOREIGN KEY (user_id)   REFERENCES users(id)           ON DELETE CASCADE
);

CREATE TABLE messages (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  thread_id   INT UNSIGNED NOT NULL,
  sender_id   INT UNSIGNED NOT NULL,
  body        TEXT NOT NULL,
  sent_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  read_at     DATETIME,

  CONSTRAINT fk_msg_thread FOREIGN KEY (thread_id) REFERENCES message_threads(id) ON DELETE CASCADE,
  CONSTRAINT fk_msg_sender FOREIGN KEY (sender_id) REFERENCES users(id)            ON DELETE CASCADE,
  INDEX idx_msg_thread (thread_id, sent_at)
);


-- =============================================================
-- SEED DATA — useful starter rows for development
-- =============================================================
INSERT INTO users (role, full_name, email, password_hash) VALUES
  ('landlord', 'Arthur Sterling', 'arthur@tenurex.dev', '$2y$10$replace_with_real_hash');

INSERT INTO amenities (name, icon) VALUES
  ('Private Parking', 'parking'),
  ('Climate Control', 'climate'),
  ('Smart Entry',     'lock');
