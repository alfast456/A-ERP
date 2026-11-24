-- ==============================================================================
-- A-ERP Database Schema: 01_master_data.sql
-- Domain: Master Data Lantai Pabrik, Mesin, Defect, Downtime & Autentikasi
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Master Pabrik (Plants)
CREATE TABLE IF NOT EXISTS `plants` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `address` TEXT NULL,
    `phone` VARCHAR(30) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Master Area Pabrik / Departemen (Factory Areas)
CREATE TABLE IF NOT EXISTS `factory_areas` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `plant_id` INT UNSIGNED NOT NULL,
    `area_code` VARCHAR(20) NOT NULL,
    `area_name` VARCHAR(100) NOT NULL,
    `department_type` ENUM('KNITTING', 'LINKING', 'SETTING', 'PACKING', 'WAREHOUSE', 'QC') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_factory_areas_plant` FOREIGN KEY (`plant_id`) REFERENCES `plants` (`id`) ON DELETE RESTRICT,
    UNIQUE KEY `uk_plant_area_code` (`plant_id`, `area_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Master Pengguna & Karyawan (Users)
CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `role` ENUM('ADMIN', 'PLANNER', 'SUPERVISOR', 'OPERATOR', 'MECHANIC', 'QA', 'WAREHOUSE') NOT NULL DEFAULT 'OPERATOR',
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Akses Pengguna ke Area Tertentu (User Area Access)
CREATE TABLE IF NOT EXISTS `user_area_access` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `factory_area_id` INT UNSIGNED NOT NULL,
    `can_write` BOOLEAN NOT NULL DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_uaa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_uaa_area` FOREIGN KEY (`factory_area_id`) REFERENCES `factory_areas` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_user_area` (`user_id`, `factory_area_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Master Tipe Mesin & Silinder (Machine Types)
CREATE TABLE IF NOT EXISTS `machine_types` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `brand` VARCHAR(50) NOT NULL,
    `model_name` VARCHAR(50) NOT NULL,
    `cylinder_inches` DECIMAL(4,2) NOT NULL COMMENT 'Contoh: 3.50, 3.75, 4.00',
    `needle_count` INT UNSIGNED NOT NULL COMMENT 'Jumlah jarum: 96, 108, 120, 144, 168, 200',
    `standard_speed_rpm` INT UNSIGNED NOT NULL DEFAULT 280,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_machine_type_specs` (`cylinder_inches`, `needle_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Master Fisik 3.000+ Mesin (Machines)
CREATE TABLE IF NOT EXISTS `machines` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `machine_type_id` INT UNSIGNED NOT NULL,
    `factory_area_id` INT UNSIGNED NOT NULL,
    `machine_number` VARCHAR(20) NOT NULL UNIQUE COMMENT 'Identitas fisik, contoh MC-001, MC-102',
    `serial_number` VARCHAR(50) NULL,
    `status` ENUM('RUNNING', 'IDLE', 'BREAKDOWN', 'MAINTENANCE', 'OFFLINE') NOT NULL DEFAULT 'IDLE',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_machines_type` FOREIGN KEY (`machine_type_id`) REFERENCES `machine_types` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_machines_area` FOREIGN KEY (`factory_area_id`) REFERENCES `factory_areas` (`id`) ON DELETE RESTRICT,
    INDEX `idx_machine_status` (`factory_area_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Master Kategori Cacat Rajut & Konveksi (Defect Types)
CREATE TABLE IF NOT EXISTS `defect_types` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `defect_code` VARCHAR(20) NOT NULL UNIQUE,
    `description` VARCHAR(100) NOT NULL,
    `category` ENUM('KNITTING_NEEDLE', 'YARN_DEFECT', 'MACHINE_TIMING', 'DIRT_OIL', 'LINKING_DEFECT', 'OTHER') NOT NULL,
    `is_reworkable` BOOLEAN NOT NULL DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Master Kategori Kerusakan & Downtime (Downtime Reasons)
CREATE TABLE IF NOT EXISTS `downtime_reasons` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `category` ENUM('MECHANICAL', 'ELECTRICAL', 'WAITING_YARN', 'WAITING_PPS', 'PREVENTIVE', 'POWER_OUTAGE') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Kalender Libur Perusahaan (Company Holidays)
CREATE TABLE IF NOT EXISTS `company_holidays` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `plant_id` INT UNSIGNED NULL COMMENT 'NULL jika berlaku untuk semua plant',
    `holiday_date` DATE NOT NULL,
    `description` VARCHAR(100) NOT NULL,
    `is_working_day` BOOLEAN NOT NULL DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_holidays_plant` FOREIGN KEY (`plant_id`) REFERENCES `plants` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_plant_date` (`plant_id`, `holiday_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
