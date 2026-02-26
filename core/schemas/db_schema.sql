-- ============================================================
--  db_schema.sql  —  Canada eTA Application Database
--  Database: dcform_db
-- ============================================================

CREATE DATABASE IF NOT EXISTS dcform_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dcform_db;

-- ── Applications ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS applications (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference        VARCHAR(30)  NOT NULL UNIQUE,
    travel_mode      ENUM('solo','group') NOT NULL DEFAULT 'solo',
    total_travellers TINYINT UNSIGNED NOT NULL DEFAULT 1,
    status           ENUM('draft','submitted','paid','processing','approved','rejected') NOT NULL DEFAULT 'draft',
    plan             ENUM('standard','priority') DEFAULT NULL,
    amount_paid      DECIMAL(10,2) DEFAULT NULL,
    payment_id       VARCHAR(100) DEFAULT NULL,
    razorpay_order   VARCHAR(100) DEFAULT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Travellers ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS travellers (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id          INT UNSIGNED NOT NULL,
    traveller_number        TINYINT UNSIGNED NOT NULL DEFAULT 1,

    -- Step 1: Contact / Trip Details
    first_name              VARCHAR(100) NOT NULL DEFAULT '',
    middle_name             VARCHAR(100) DEFAULT NULL,
    last_name               VARCHAR(100) NOT NULL DEFAULT '',
    email                   VARCHAR(255) NOT NULL DEFAULT '',
    phone                   VARCHAR(30)  NOT NULL DEFAULT '',
    travel_date             DATE DEFAULT NULL,
    purpose_of_visit        VARCHAR(100) DEFAULT NULL,

    -- Step 2: Personal Details
    date_of_birth           DATE DEFAULT NULL,
    gender                  ENUM('male','female','other') DEFAULT NULL,
    country_of_birth        VARCHAR(100) DEFAULT NULL,
    city_of_birth           VARCHAR(100) DEFAULT NULL,
    marital_status          VARCHAR(30)  DEFAULT NULL,
    nationality             VARCHAR(100) DEFAULT NULL,

    -- Step 3: Passport Details
    passport_country            VARCHAR(100) DEFAULT NULL,
    passport_number             VARCHAR(50)  DEFAULT NULL,
    passport_issue_date         DATE DEFAULT NULL,
    passport_expiry             DATE DEFAULT NULL,
    dual_citizen                TINYINT(1) NOT NULL DEFAULT 0,
    other_citizenship_country   VARCHAR(100) DEFAULT NULL,
    prev_canada_app             TINYINT(1) NOT NULL DEFAULT 0,
    uci_number                  VARCHAR(50)  DEFAULT NULL,

    -- Step 4: Residential Details
    address_line            VARCHAR(255) DEFAULT NULL,
    street_number           VARCHAR(100) DEFAULT NULL,
    apartment_number        VARCHAR(50)  DEFAULT NULL,
    country                 VARCHAR(100) DEFAULT NULL,
    city                    VARCHAR(100) DEFAULT NULL,
    postal_code             VARCHAR(20)  DEFAULT NULL,
    state                   VARCHAR(100) DEFAULT NULL,
    occupation              VARCHAR(100) DEFAULT NULL,
    has_job                 TINYINT(1)   NOT NULL DEFAULT 0,
    job_title               VARCHAR(150) DEFAULT NULL,
    employer_name           VARCHAR(200) DEFAULT NULL,
    employer_country        VARCHAR(100) DEFAULT NULL,
    employer_city           VARCHAR(100) DEFAULT NULL,
    start_year              YEAR         DEFAULT NULL,

    -- Step 5: Background Questions
    visa_refusal            TINYINT(1) NOT NULL DEFAULT 0,
    visa_refusal_details    TEXT DEFAULT NULL,
    tuberculosis            TINYINT(1) NOT NULL DEFAULT 0,
    tuberculosis_details    TEXT DEFAULT NULL,
    criminal_history        TINYINT(1) NOT NULL DEFAULT 0,
    criminal_details        TEXT DEFAULT NULL,
    health_condition        VARCHAR(100) DEFAULT NULL,

    -- Step 6: Declaration
    decl_accurate           TINYINT(1) NOT NULL DEFAULT 0,
    decl_terms              TINYINT(1) NOT NULL DEFAULT 0,
    step_completed          VARCHAR(30) DEFAULT NULL,

    created_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    INDEX idx_application (application_id),
    INDEX idx_traveller_num (application_id, traveller_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Payments ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS payments (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id      INT UNSIGNED NOT NULL,
    razorpay_order_id   VARCHAR(100) DEFAULT NULL,
    razorpay_payment_id VARCHAR(100) DEFAULT NULL,
    razorpay_signature  VARCHAR(255) DEFAULT NULL,
    amount              DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    currency            VARCHAR(10)   NOT NULL DEFAULT 'INR',
    status              ENUM('created','captured','failed','refunded') NOT NULL DEFAULT 'created',
    plan                ENUM('standard','priority') DEFAULT NULL,
    billing_first_name  VARCHAR(100) DEFAULT NULL,
    billing_last_name   VARCHAR(100) DEFAULT NULL,
    billing_email       VARCHAR(255) DEFAULT NULL,
    billing_address     VARCHAR(255) DEFAULT NULL,
    billing_city        VARCHAR(100) DEFAULT NULL,
    billing_country     VARCHAR(100) DEFAULT NULL,
    billing_state       VARCHAR(100) DEFAULT NULL,
    billing_zip         VARCHAR(20)  DEFAULT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Lookup: Countries ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS countries (
    id         SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    code       CHAR(2)      NOT NULL,
    phone_code VARCHAR(10)  DEFAULT NULL,
    is_active  TINYINT(1)   NOT NULL DEFAULT 1,
    UNIQUE KEY uk_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Lookup: States / Provinces ────────────────────────────
CREATE TABLE IF NOT EXISTS states (
    id         SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    country_id SMALLINT UNSIGNED NOT NULL,
    name       VARCHAR(100) NOT NULL,
    FOREIGN KEY (country_id) REFERENCES countries(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Lookup: Visit Purposes ────────────────────────────────
CREATE TABLE IF NOT EXISTS visit_purposes (
    id        TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name      VARCHAR(100) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Lookup: Occupations ───────────────────────────────────
CREATE TABLE IF NOT EXISTS occupations (
    id         TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    needs_job  TINYINT(1) NOT NULL DEFAULT 1,
    is_active  TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Lookup: Job Titles ────────────────────────────────────
CREATE TABLE IF NOT EXISTS job_titles (
    id        SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name      VARCHAR(150) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ══════════════════════════════════════════════════════════
--  SEED DATA
-- ══════════════════════════════════════════════════════════

-- Visit Purposes
INSERT IGNORE INTO visit_purposes (name) VALUES
('Tourism / Holiday'),('Business'),('Education / Study'),('Work'),
('Medical Treatment'),('Transit'),('Family Visit'),('Conference / Event');

-- Occupations
INSERT IGNORE INTO occupations (name, needs_job) VALUES
('Employed',1),('Self-Employed',1),('Student',1),('Government Employee',1),
('Military / Defence',1),('Healthcare Worker',1),('Teacher / Professor',1),
('Retired',0),('Unemployed',0),('Homemaker',0);

-- Job Titles (sample)
INSERT IGNORE INTO job_titles (name) VALUES
('Software Engineer'),('Doctor'),('Teacher'),('Accountant'),('Manager'),
('Nurse'),('Lawyer'),('Architect'),('Pilot'),('Chef'),('Engineer'),
('Designer'),('Analyst'),('Consultant'),('Director'),('Officer'),
('Technician'),('Administrator'),('Professor'),('Researcher');

-- Countries (major ones)
INSERT IGNORE INTO countries (name, code, phone_code) VALUES
('Afghanistan','AF','+93'),('Albania','AL','+355'),('Algeria','DZ','+213'),
('Argentina','AR','+54'),('Australia','AU','+61'),('Austria','AT','+43'),
('Bangladesh','BD','+880'),('Belgium','BE','+32'),('Brazil','BR','+55'),
('Canada','CA','+1'),('China','CN','+86'),('Colombia','CO','+57'),
('Denmark','DK','+45'),('Egypt','EG','+20'),('Ethiopia','ET','+251'),
('Finland','FI','+358'),('France','FR','+33'),('Germany','DE','+49'),
('Ghana','GH','+233'),('Greece','GR','+30'),('India','IN','+91'),
('Indonesia','ID','+62'),('Iran','IR','+98'),('Iraq','IQ','+964'),
('Ireland','IE','+353'),('Israel','IL','+972'),('Italy','IT','+39'),
('Japan','JP','+81'),('Jordan','JO','+962'),('Kenya','KE','+254'),
('Malaysia','MY','+60'),('Mexico','MX','+52'),('Morocco','MA','+212'),
('Nepal','NP','+977'),('Netherlands','NL','+31'),('New Zealand','NZ','+64'),
('Nigeria','NG','+234'),('Norway','NO','+47'),('Pakistan','PK','+92'),
('Philippines','PH','+63'),('Poland','PL','+48'),('Portugal','PT','+351'),
('Romania','RO','+40'),('Russia','RU','+7'),('Saudi Arabia','SA','+966'),
('Singapore','SG','+65'),('South Africa','ZA','+27'),('South Korea','KR','+82'),
('Spain','ES','+34'),('Sri Lanka','LK','+94'),('Sweden','SE','+46'),
('Switzerland','CH','+41'),('Tanzania','TZ','+255'),('Thailand','TH','+66'),
('Turkey','TR','+90'),('Uganda','UG','+256'),('Ukraine','UA','+380'),
('United Arab Emirates','AE','+971'),('United Kingdom','GB','+44'),
('United States','US','+1'),('Vietnam','VN','+84'),('Zimbabwe','ZW','+263');

-- States for India
SET @in = (SELECT id FROM countries WHERE code='IN');
INSERT IGNORE INTO states (country_id, name) VALUES
(@in,'Andhra Pradesh'),(@in,'Arunachal Pradesh'),(@in,'Assam'),(@in,'Bihar'),
(@in,'Chhattisgarh'),(@in,'Goa'),(@in,'Gujarat'),(@in,'Haryana'),
(@in,'Himachal Pradesh'),(@in,'Jharkhand'),(@in,'Karnataka'),(@in,'Kerala'),
(@in,'Madhya Pradesh'),(@in,'Maharashtra'),(@in,'Manipur'),(@in,'Meghalaya'),
(@in,'Mizoram'),(@in,'Nagaland'),(@in,'Odisha'),(@in,'Punjab'),
(@in,'Rajasthan'),(@in,'Sikkim'),(@in,'Tamil Nadu'),(@in,'Telangana'),
(@in,'Tripura'),(@in,'Uttar Pradesh'),(@in,'Uttarakhand'),(@in,'West Bengal'),
(@in,'Delhi'),(@in,'Jammu & Kashmir'),(@in,'Ladakh');

-- States for USA
SET @us = (SELECT id FROM countries WHERE code='US');
INSERT IGNORE INTO states (country_id, name) VALUES
(@us,'Alabama'),(@us,'Alaska'),(@us,'Arizona'),(@us,'Arkansas'),(@us,'California'),
(@us,'Colorado'),(@us,'Connecticut'),(@us,'Delaware'),(@us,'Florida'),(@us,'Georgia'),
(@us,'Hawaii'),(@us,'Idaho'),(@us,'Illinois'),(@us,'Indiana'),(@us,'Iowa'),
(@us,'Kansas'),(@us,'Kentucky'),(@us,'Louisiana'),(@us,'Maine'),(@us,'Maryland'),
(@us,'Massachusetts'),(@us,'Michigan'),(@us,'Minnesota'),(@us,'Mississippi'),
(@us,'Missouri'),(@us,'Montana'),(@us,'Nebraska'),(@us,'Nevada'),
(@us,'New Hampshire'),(@us,'New Jersey'),(@us,'New Mexico'),(@us,'New York'),
(@us,'North Carolina'),(@us,'North Dakota'),(@us,'Ohio'),(@us,'Oklahoma'),
(@us,'Oregon'),(@us,'Pennsylvania'),(@us,'Rhode Island'),(@us,'South Carolina'),
(@us,'South Dakota'),(@us,'Tennessee'),(@us,'Texas'),(@us,'Utah'),
(@us,'Vermont'),(@us,'Virginia'),(@us,'Washington'),(@us,'West Virginia'),
(@us,'Wisconsin'),(@us,'Wyoming');

-- States for Canada
SET @ca = (SELECT id FROM countries WHERE code='CA');
INSERT IGNORE INTO states (country_id, name) VALUES
(@ca,'Alberta'),(@ca,'British Columbia'),(@ca,'Manitoba'),(@ca,'New Brunswick'),
(@ca,'Newfoundland and Labrador'),(@ca,'Northwest Territories'),(@ca,'Nova Scotia'),
(@ca,'Nunavut'),(@ca,'Ontario'),(@ca,'Prince Edward Island'),(@ca,'Quebec'),
(@ca,'Saskatchewan'),(@ca,'Yukon');

-- States for UK
SET @gb = (SELECT id FROM countries WHERE code='GB');
INSERT IGNORE INTO states (country_id, name) VALUES
(@gb,'England'),(@gb,'Scotland'),(@gb,'Wales'),(@gb,'Northern Ireland');

-- States for Australia
SET @au = (SELECT id FROM countries WHERE code='AU');
INSERT IGNORE INTO states (country_id, name) VALUES
(@au,'New South Wales'),(@au,'Victoria'),(@au,'Queensland'),
(@au,'South Australia'),(@au,'Western Australia'),(@au,'Tasmania'),
(@au,'Australian Capital Territory'),(@au,'Northern Territory');
