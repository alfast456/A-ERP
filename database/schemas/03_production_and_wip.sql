-- ==============================================================================
-- A-ERP Database Schema: 03_production_and_wip.sql
-- Domain: Eksekusi Produksi (Box Rajut), Defect/BS, Rework & WIP Routing
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Transaksi Output Fisik Box Rajut (Production Boxes)
-- Tabel volume tinggi (>2 Juta baris). Primary key komposit menyertakan production_date untuk partisi.
CREATE TABLE IF NOT EXISTS `production_boxes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `box_barcode` VARCHAR(64) NOT NULL COMMENT 'Barcode unik fisik box kaos kaki',
    `production_date` DATE NOT NULL,
    `shift` TINYINT UNSIGNED NOT NULL COMMENT '1, 2, atau 3',
    `order_shipment_id` BIGINT UNSIGNED NOT NULL,
    `machine_id` INT UNSIGNED NOT NULL,
    `operator_id` BIGINT UNSIGNED NOT NULL,
    `good_pairs` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Pasang lolos QC',
    `defect_pairs` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Pasang BS / cacat',
    `weight_kg` DECIMAL(6,3) NOT NULL DEFAULT 0.000,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`, `production_date`),
    UNIQUE KEY `uk_box_barcode_date` (`box_barcode`, `production_date`),
    INDEX `idx_prod_shipment` (`order_shipment_id`),
    INDEX `idx_prod_machine_date` (`machine_id`, `production_date`),
    INDEX `idx_prod_operator` (`operator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Detail Cacat per Box Hasil Rajutan (Box Defects)
CREATE TABLE IF NOT EXISTS `box_defects` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `production_box_id` BIGINT UNSIGNED NOT NULL,
    `defect_type_id` INT UNSIGNED NOT NULL,
    `defect_count_pairs` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `notes` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_box_defects_type` FOREIGN KEY (`defect_type_id`) REFERENCES `defect_types` (`id`) ON DELETE RESTRICT,
    INDEX `idx_box_defects_box` (`production_box_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Siklus Jalur Perbaikan Kaos Kaki Cacat (Rework Tracking)
CREATE TABLE IF NOT EXISTS `rework_tracking` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `production_box_id` BIGINT UNSIGNED NOT NULL,
    `defect_type_id` INT UNSIGNED NOT NULL,
    `sent_pairs` SMALLINT UNSIGNED NOT NULL,
    `repaired_pairs` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Sukses diperbaiki',
    `scrapped_pairs` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'BS murni tidak tertolong',
    `status` ENUM('IN_REPAIR', 'RESOLVED', 'SCRAPPED') NOT NULL DEFAULT 'IN_REPAIR',
    `repair_area_id` INT UNSIGNED NOT NULL,
    `technician_id` BIGINT UNSIGNED NULL,
    `repaired_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_rework_defect_type` FOREIGN KEY (`defect_type_id`) REFERENCES `defect_types` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_rework_area` FOREIGN KEY (`repair_area_id`) REFERENCES `factory_areas` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_rework_technician` FOREIGN KEY (`technician_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_rework_status` (`status`, `repair_area_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Template Jalur Proses / Rute Manufaktur (Flow Templates)
CREATE TABLE IF NOT EXISTS `flow_templates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT 'Contoh: Standard Crew Sock, Low-Cut Ankle',
    `description` TEXT NULL,
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Detail Langkah Alur Rute (Flow Steps)
CREATE TABLE IF NOT EXISTS `flow_steps` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `flow_template_id` INT UNSIGNED NOT NULL,
    `step_order` TINYINT UNSIGNED NOT NULL COMMENT '1=Knitting, 2=Linking, 3=Setting, etc.',
    `department_type` ENUM('KNITTING', 'LINKING', 'SETTING', 'PACKING', 'WAREHOUSE', 'QC') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_flow_steps_template` FOREIGN KEY (`flow_template_id`) REFERENCES `flow_templates` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_flow_step_order` (`flow_template_id`, `step_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Log Audit Perpindahan Fisik Box Antar-Departemen (WIP Movements)
-- Menggantikan tabel outflow horizontal dengan desain normalized event log
CREATE TABLE IF NOT EXISTS `wip_movements` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `movement_date` DATE NOT NULL,
    `production_box_id` BIGINT UNSIGNED NOT NULL,
    `from_area_id` INT UNSIGNED NOT NULL,
    `to_area_id` INT UNSIGNED NOT NULL,
    `sent_pairs` INT UNSIGNED NOT NULL,
    `received_pairs` INT UNSIGNED NOT NULL DEFAULT 0,
    `dispatched_by` BIGINT UNSIGNED NOT NULL,
    `received_by` BIGINT UNSIGNED NULL,
    `status` ENUM('DISPATCHED', 'RECEIVED', 'DISCREPANCY') NOT NULL DEFAULT 'DISPATCHED',
    `remarks` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`, `movement_date`),
    INDEX `idx_wip_box` (`production_box_id`),
    INDEX `idx_wip_from_to` (`from_area_id`, `to_area_id`),
    INDEX `idx_wip_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
