-- ==============================================================================
-- A-ERP Database Schema: 04_logistics_and_maintenance.sql
-- Domain: Logistik Benang Lantai, Maintenance Mesin, Konsumsi Jarum & Audit Trail
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Saldo Benang di Lantai Rajut (Yarn Stock Balances)
-- Menggantikan stock_area: Pelacakan stok real-time per lokasi area rajut
CREATE TABLE IF NOT EXISTS `yarn_stock_balances` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `factory_area_id` INT UNSIGNED NOT NULL,
    `yarn_code` VARCHAR(50) NOT NULL COMMENT 'Kode spesifikasi benang',
    `yarn_name` VARCHAR(100) NOT NULL,
    `color_name` VARCHAR(50) NOT NULL,
    `lot_number` VARCHAR(50) NOT NULL,
    `qty_cones` INT UNSIGNED NOT NULL DEFAULT 0,
    `weight_kg` DECIMAL(10,3) NOT NULL DEFAULT 0.000,
    `last_updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_yarn_stock_area` FOREIGN KEY (`factory_area_id`) REFERENCES `factory_areas` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_yarn_area_lot` (`factory_area_id`, `yarn_code`, `lot_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Mutasi Keluar/Masuk Benang Lantai (Yarn Transactions)
-- Menggantikan pemasukan_stock_area: double-entry audit mutasi benang
CREATE TABLE IF NOT EXISTS `yarn_transactions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `yarn_stock_balance_id` BIGINT UNSIGNED NOT NULL,
    `transaction_type` ENUM('RECEIPT_FROM_WH', 'RETURN_TO_WH', 'USAGE_KNITTING', 'ADJUSTMENT') NOT NULL,
    `cones_delta` INT NOT NULL,
    `weight_kg_delta` DECIMAL(10,3) NOT NULL,
    `reference_number` VARCHAR(50) NULL COMMENT 'No Surat Jalan / PDK',
    `performed_by` BIGINT UNSIGNED NOT NULL,
    `transaction_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_yarn_trx_balance` FOREIGN KEY (`yarn_stock_balance_id`) REFERENCES `yarn_stock_balances` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_yarn_trx_user` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Pencatatan Kejadian Kerusakan Mesin (Machine Downtimes)
-- Menggantikan downtime legacy (1,38M baris) dengan relasi integer yang terindeks
CREATE TABLE IF NOT EXISTS `machine_downtimes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `machine_id` INT UNSIGNED NOT NULL,
    `downtime_reason_id` INT UNSIGNED NOT NULL,
    `start_time` DATETIME NOT NULL,
    `end_time` DATETIME NULL,
    `duration_minutes` INT UNSIGNED GENERATED ALWAYS AS (
        CASE WHEN `end_time` IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, `start_time`, `end_time`) ELSE NULL END
    ) VIRTUAL,
    `reported_by` BIGINT UNSIGNED NOT NULL,
    `technician_id` BIGINT UNSIGNED NULL,
    `action_taken` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_downtime_machine` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_downtime_reason` FOREIGN KEY (`downtime_reason_id`) REFERENCES `downtime_reasons` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_downtime_reporter` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_downtime_tech` FOREIGN KEY (`technician_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_downtime_machine_time` (`machine_id`, `start_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Penggunaan Jarum Mesin Rajut (Needle Consumptions)
-- Menggantikan tabel penggunaan_jarum & audit konsumsi sparepart jarum
CREATE TABLE IF NOT EXISTS `needle_consumptions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `machine_id` INT UNSIGNED NOT NULL,
    `replacement_date` DATE NOT NULL,
    `shift` TINYINT UNSIGNED NOT NULL,
    `needle_type` VARCHAR(50) NOT NULL COMMENT 'Tipe/Kode jarum rajut',
    `broken_count` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `replaced_count` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `reason` ENUM('BENT', 'BROKEN_HOOK', 'WEAR_TEAR', 'CRASH') NOT NULL DEFAULT 'BROKEN_HOOK',
    `mechanic_id` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_needle_machine` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_needle_mechanic` FOREIGN KEY (`mechanic_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    INDEX `idx_needle_mc_date` (`machine_id`, `replacement_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tiket Pengaduan & Service Mesin (Maintenance Tickets)
-- Menggantikan pengaduan, data_maintenance, data_service
CREATE TABLE IF NOT EXISTS `maintenance_tickets` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ticket_number` VARCHAR(30) NOT NULL UNIQUE,
    `machine_id` INT UNSIGNED NOT NULL,
    `priority` ENUM('LOW', 'MEDIUM', 'HIGH', 'URGENT') NOT NULL DEFAULT 'MEDIUM',
    `issue_description` TEXT NOT NULL,
    `reported_by` BIGINT UNSIGNED NOT NULL,
    `assigned_to` BIGINT UNSIGNED NULL,
    `status` ENUM('OPEN', 'IN_PROGRESS', 'RESOLVED', 'CLOSED') NOT NULL DEFAULT 'OPEN',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `resolved_at` TIMESTAMP NULL,
    CONSTRAINT `fk_ticket_machine` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ticket_reporter` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_ticket_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_ticket_status` (`status`, `priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Percakapan / Update Progres Tiket (Maintenance Ticket Replies)
-- Menggantikan pengaduan_reply
CREATE TABLE IF NOT EXISTS `maintenance_ticket_replies` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `maintenance_ticket_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_reply_ticket` FOREIGN KEY (`maintenance_ticket_id`) REFERENCES `maintenance_tickets` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reply_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Audit Log Sistem Enterprise (Audit Logs)
-- Menggantikan audit_logs legacy dengan indexing optimal
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(50) NOT NULL COMMENT 'CREATE, UPDATE, DELETE, REVISE, LOGIN',
    `table_name` VARCHAR(50) NOT NULL,
    `record_id` BIGINT UNSIGNED NOT NULL,
    `old_values` JSON NULL,
    `new_values` JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_table_record` (`table_name`, `record_id`),
    INDEX `idx_audit_user_action` (`user_id`, `action`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
