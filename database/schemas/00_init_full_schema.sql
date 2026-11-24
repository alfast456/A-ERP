-- ==============================================================================
-- A-ERP Database Master Initializer: 00_init_full_schema.sql
-- Proyek: A-ERP (Garment & Knitting Plant Division)
-- Deskripsi: Skrip DDL komprehensif 22 tabel, live views, dan master initial seed.
-- Karakteristik: Zero-Downtime, Integer Foreign Keys, BigInt IDs, Partition-Ready
-- ==============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------------
-- 1. MASTER DATA LANTAI PABRIK & MESIN
-- ------------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `plants` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `address` TEXT NULL,
    `phone` VARCHAR(30) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `factory_areas` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `plant_id` INT UNSIGNED NOT NULL,
    `area_code` VARCHAR(20) NOT NULL,
    `area_name` VARCHAR(100) NOT NULL,
    `department_type` ENUM('KNITTING', 'LINKING', 'SETTING', 'PACKING', 'WAREHOUSE', 'QC') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_fa_plant` FOREIGN KEY (`plant_id`) REFERENCES `plants` (`id`) ON DELETE RESTRICT,
    UNIQUE KEY `uk_plant_area_code` (`plant_id`, `area_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS `machine_types` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `brand` VARCHAR(50) NOT NULL,
    `model_name` VARCHAR(50) NOT NULL,
    `cylinder_inches` DECIMAL(4,2) NOT NULL,
    `needle_count` INT UNSIGNED NOT NULL,
    `standard_speed_rpm` INT UNSIGNED NOT NULL DEFAULT 280,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_machine_type_specs` (`cylinder_inches`, `needle_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `machines` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `machine_type_id` INT UNSIGNED NOT NULL,
    `factory_area_id` INT UNSIGNED NOT NULL,
    `machine_number` VARCHAR(20) NOT NULL UNIQUE,
    `serial_number` VARCHAR(50) NULL,
    `status` ENUM('RUNNING', 'IDLE', 'BREAKDOWN', 'MAINTENANCE', 'OFFLINE') NOT NULL DEFAULT 'IDLE',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_mc_type` FOREIGN KEY (`machine_type_id`) REFERENCES `machine_types` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_mc_area` FOREIGN KEY (`factory_area_id`) REFERENCES `factory_areas` (`id`) ON DELETE RESTRICT,
    INDEX `idx_mc_status` (`factory_area_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `defect_types` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `defect_code` VARCHAR(20) NOT NULL UNIQUE,
    `description` VARCHAR(100) NOT NULL,
    `category` ENUM('KNITTING_NEEDLE', 'YARN_DEFECT', 'MACHINE_TIMING', 'DIRT_OIL', 'LINKING_DEFECT', 'OTHER') NOT NULL,
    `is_reworkable` BOOLEAN NOT NULL DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `downtime_reasons` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `category` ENUM('MECHANICAL', 'ELECTRICAL', 'WAITING_YARN', 'WAITING_PPS', 'PREVENTIVE', 'POWER_OUTAGE') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `company_holidays` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `plant_id` INT UNSIGNED NULL,
    `holiday_date` DATE NOT NULL,
    `description` VARCHAR(100) NOT NULL,
    `is_working_day` BOOLEAN NOT NULL DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ch_plant` FOREIGN KEY (`plant_id`) REFERENCES `plants` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_plant_holiday_date` (`plant_id`, `holiday_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. ORDER, DELIVERY, REVISION & PLANNING
-- ------------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `orders` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `po_number` VARCHAR(50) NOT NULL UNIQUE,
    `buyer_name` VARCHAR(100) NOT NULL,
    `season` VARCHAR(30) NULL,
    `order_type` ENUM('BULK', 'SAMPLE', 'REMAKE', 'PROMO') NOT NULL DEFAULT 'BULK',
    `total_ordered_pairs` INT UNSIGNED NOT NULL DEFAULT 0,
    `order_date` DATE NOT NULL,
    `status` ENUM('DRAFT', 'CONFIRMED', 'IN_PLANNING', 'IN_PRODUCTION', 'COMPLETED', 'CANCELLED') NOT NULL DEFAULT 'CONFIRMED',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_orders_status_date` (`status`, `order_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_styles` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `machine_type_id` INT UNSIGNED NOT NULL,
    `style_code` VARCHAR(50) NOT NULL,
    `color_code` VARCHAR(30) NOT NULL,
    `size` VARCHAR(20) NOT NULL,
    `smv_seconds` DECIMAL(6,2) NOT NULL DEFAULT 120.00,
    `unit_weight_gram` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    `gw_dz` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_os_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_os_mctype` FOREIGN KEY (`machine_type_id`) REFERENCES `machine_types` (`id`) ON DELETE RESTRICT,
    INDEX `idx_style_lookup` (`style_code`, `color_code`, `size`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_shipments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_style_id` BIGINT UNSIGNED NOT NULL,
    `shipment_index` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `target_delivery_date` DATE NOT NULL,
    `qty_pairs` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_shipments_style` FOREIGN KEY (`order_style_id`) REFERENCES `order_styles` (`id`) ON DELETE CASCADE,
    INDEX `idx_shipment_delivery` (`target_delivery_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_revisions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `revision_number` SMALLINT UNSIGNED NOT NULL,
    `revision_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `reason_notes` TEXT NOT NULL,
    `requested_by` BIGINT UNSIGNED NOT NULL,
    `approved_by` BIGINT UNSIGNED NULL,
    `status` ENUM('PENDING', 'APPROVED', 'REJECTED') NOT NULL DEFAULT 'APPROVED',
    CONSTRAINT `fk_rev_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rev_req_user` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_rev_app_user` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    UNIQUE KEY `uk_order_rev_no` (`order_id`, `revision_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_revision_items` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_revision_id` BIGINT UNSIGNED NOT NULL,
    `order_shipment_id` BIGINT UNSIGNED NOT NULL,
    `old_qty` INT UNSIGNED NOT NULL,
    `new_qty` INT UNSIGNED NOT NULL,
    `delta_qty` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ori_revision` FOREIGN KEY (`order_revision_id`) REFERENCES `order_revisions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ori_shipment` FOREIGN KEY (`order_shipment_id`) REFERENCES `order_shipments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sample_approvals` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_style_id` BIGINT UNSIGNED NOT NULL,
    `machine_id` INT UNSIGNED NOT NULL,
    `sample_stage` ENUM('PROTO', 'FIT', 'PRE_PRODUCTION', 'PILOT') NOT NULL DEFAULT 'PRE_PRODUCTION',
    `mechanic_approved_by` BIGINT UNSIGNED NULL,
    `qa_approved_by` BIGINT UNSIGNED NULL,
    `merchandiser_approved_by` BIGINT UNSIGNED NULL,
    `is_approved_for_bulk` BOOLEAN NOT NULL DEFAULT FALSE,
    `remarks` TEXT NULL,
    `approved_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_sa_style` FOREIGN KEY (`order_style_id`) REFERENCES `order_styles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sa_machine` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_sa_mechanic` FOREIGN KEY (`mechanic_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_sa_qa` FOREIGN KEY (`qa_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_sa_md` FOREIGN KEY (`merchandiser_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `planning_allocations` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `factory_area_id` INT UNSIGNED NOT NULL,
    `allocated_pairs` INT UNSIGNED NOT NULL,
    `target_start_date` DATE NOT NULL,
    `target_end_date` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_pa_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pa_area` FOREIGN KEY (`factory_area_id`) REFERENCES `factory_areas` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `machine_assignments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `planning_allocation_id` BIGINT UNSIGNED NOT NULL,
    `machine_id` INT UNSIGNED NOT NULL,
    `order_shipment_id` BIGINT UNSIGNED NOT NULL,
    `plan_date` DATE NOT NULL,
    `target_dz` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    `target_pairs` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ma_allocation` FOREIGN KEY (`planning_allocation_id`) REFERENCES `planning_allocations` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ma_machine` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_ma_shipment` FOREIGN KEY (`order_shipment_id`) REFERENCES `order_shipments` (`id`) ON DELETE CASCADE,
    INDEX `idx_plan_machine_date` (`machine_id`, `plan_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `capacity_forecasts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `factory_area_id` INT UNSIGNED NOT NULL,
    `machine_type_id` INT UNSIGNED NOT NULL,
    `forecast_period` VARCHAR(7) NOT NULL,
    `active_machines_count` INT UNSIGNED NOT NULL,
    `available_hours` DECIMAL(10,2) NOT NULL,
    `required_hours` DECIMAL(10,2) NOT NULL,
    `load_percentage` DECIMAL(5,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_cf_area` FOREIGN KEY (`factory_area_id`) REFERENCES `factory_areas` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cf_mctype` FOREIGN KEY (`machine_type_id`) REFERENCES `machine_types` (`id`) ON DELETE RESTRICT,
    UNIQUE KEY `uk_cf_period_type` (`factory_area_id`, `machine_type_id`, `forecast_period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `monthly_targets` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `plant_id` INT UNSIGNED NOT NULL,
    `target_month` VARCHAR(7) NOT NULL,
    `target_export_pairs` INT UNSIGNED NOT NULL DEFAULT 0,
    `target_local_pairs` INT UNSIGNED NOT NULL DEFAULT 0,
    `target_efficiency` DECIMAL(5,2) NOT NULL DEFAULT 85.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_mt_plant` FOREIGN KEY (`plant_id`) REFERENCES `plants` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_mt_plant_month` (`plant_id`, `target_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_bookings` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `buyer_name` VARCHAR(100) NOT NULL,
    `booking_code` VARCHAR(50) NOT NULL UNIQUE,
    `machine_type_id` INT UNSIGNED NOT NULL,
    `estimated_pairs` INT UNSIGNED NOT NULL,
    `target_month` VARCHAR(7) NOT NULL,
    `notes` TEXT NULL,
    `status` ENUM('HOLD', 'CONFIRMED_TO_ORDER', 'EXPIRED', 'CANCELLED') NOT NULL DEFAULT 'HOLD',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ob_mctype` FOREIGN KEY (`machine_type_id`) REFERENCES `machine_types` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 3. EKSEKUSI PRODUKSI, QUALITY & WIP ROUTING
-- ------------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `production_boxes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `box_barcode` VARCHAR(64) NOT NULL,
    `production_date` DATE NOT NULL,
    `shift` TINYINT UNSIGNED NOT NULL,
    `order_shipment_id` BIGINT UNSIGNED NOT NULL,
    `machine_id` INT UNSIGNED NOT NULL,
    `operator_id` BIGINT UNSIGNED NOT NULL,
    `good_pairs` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `defect_pairs` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `weight_kg` DECIMAL(6,3) NOT NULL DEFAULT 0.000,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`, `production_date`),
    UNIQUE KEY `uk_box_barcode_date` (`box_barcode`, `production_date`),
    INDEX `idx_pb_shipment` (`order_shipment_id`),
    INDEX `idx_pb_machine_date` (`machine_id`, `production_date`),
    INDEX `idx_pb_operator` (`operator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `box_defects` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `production_box_id` BIGINT UNSIGNED NOT NULL,
    `defect_type_id` INT UNSIGNED NOT NULL,
    `defect_count_pairs` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `notes` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_bd_defect_type` FOREIGN KEY (`defect_type_id`) REFERENCES `defect_types` (`id`) ON DELETE RESTRICT,
    INDEX `idx_bd_box` (`production_box_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rework_tracking` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `production_box_id` BIGINT UNSIGNED NOT NULL,
    `defect_type_id` INT UNSIGNED NOT NULL,
    `sent_pairs` SMALLINT UNSIGNED NOT NULL,
    `repaired_pairs` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `scrapped_pairs` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `status` ENUM('IN_REPAIR', 'RESOLVED', 'SCRAPPED') NOT NULL DEFAULT 'IN_REPAIR',
    `repair_area_id` INT UNSIGNED NOT NULL,
    `technician_id` BIGINT UNSIGNED NULL,
    `repaired_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_rt_defect_type` FOREIGN KEY (`defect_type_id`) REFERENCES `defect_types` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_rt_area` FOREIGN KEY (`repair_area_id`) REFERENCES `factory_areas` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_rt_technician` FOREIGN KEY (`technician_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_rt_status` (`status`, `repair_area_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `flow_templates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `flow_steps` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `flow_template_id` INT UNSIGNED NOT NULL,
    `step_order` TINYINT UNSIGNED NOT NULL,
    `department_type` ENUM('KNITTING', 'LINKING', 'SETTING', 'PACKING', 'WAREHOUSE', 'QC') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_fs_template` FOREIGN KEY (`flow_template_id`) REFERENCES `flow_templates` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_fs_order` (`flow_template_id`, `step_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    INDEX `idx_wm_box` (`production_box_id`),
    INDEX `idx_wm_from_to` (`from_area_id`, `to_area_id`),
    INDEX `idx_wm_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4. LOGISTIK BENANG, MAINTENANCE & AUDIT TRAIL
-- ------------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `yarn_stock_balances` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `factory_area_id` INT UNSIGNED NOT NULL,
    `yarn_code` VARCHAR(50) NOT NULL,
    `yarn_name` VARCHAR(100) NOT NULL,
    `color_name` VARCHAR(50) NOT NULL,
    `lot_number` VARCHAR(50) NOT NULL,
    `qty_cones` INT UNSIGNED NOT NULL DEFAULT 0,
    `weight_kg` DECIMAL(10,3) NOT NULL DEFAULT 0.000,
    `last_updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ysb_area` FOREIGN KEY (`factory_area_id`) REFERENCES `factory_areas` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_yarn_area_lot` (`factory_area_id`, `yarn_code`, `lot_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `yarn_transactions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `yarn_stock_balance_id` BIGINT UNSIGNED NOT NULL,
    `transaction_type` ENUM('RECEIPT_FROM_WH', 'RETURN_TO_WH', 'USAGE_KNITTING', 'ADJUSTMENT') NOT NULL,
    `cones_delta` INT NOT NULL,
    `weight_kg_delta` DECIMAL(10,3) NOT NULL,
    `reference_number` VARCHAR(50) NULL,
    `performed_by` BIGINT UNSIGNED NOT NULL,
    `transaction_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_yt_balance` FOREIGN KEY (`yarn_stock_balance_id`) REFERENCES `yarn_stock_balances` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_yt_user` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `machine_downtimes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
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
    CONSTRAINT `fk_md_machine` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_md_reason` FOREIGN KEY (`downtime_reason_id`) REFERENCES `downtime_reasons` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_md_reporter` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_md_technician` FOREIGN KEY (`technician_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_md_mc_time` (`machine_id`, `start_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `needle_consumptions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `machine_id` INT UNSIGNED NOT NULL,
    `replacement_date` DATE NOT NULL,
    `shift` TINYINT UNSIGNED NOT NULL,
    `needle_type` VARCHAR(50) NOT NULL,
    `broken_count` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `replaced_count` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `reason` ENUM('BENT', 'BROKEN_HOOK', 'WEAR_TEAR', 'CRASH') NOT NULL DEFAULT 'BROKEN_HOOK',
    `mechanic_id` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_nc_machine` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_nc_mechanic` FOREIGN KEY (`mechanic_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    INDEX `idx_nc_mc_date` (`machine_id`, `replacement_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    CONSTRAINT `fk_mtk_machine` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_mtk_reporter` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_mtk_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_mtk_status` (`status`, `priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `maintenance_ticket_replies` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `maintenance_ticket_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_mtr_ticket` FOREIGN KEY (`maintenance_ticket_id`) REFERENCES `maintenance_tickets` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_mtr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(50) NOT NULL,
    `table_name` VARCHAR(50) NOT NULL,
    `record_id` BIGINT UNSIGNED NOT NULL,
    `old_values` JSON NULL,
    `new_values` JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_al_table_record` (`table_name`, `record_id`),
    INDEX `idx_al_user_action` (`user_id`, `action`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 5. REVOLUTIONARY VIEWS: ELIMINASI GOD-MODE UPDATE SISA
-- ------------------------------------------------------------------------------

-- View: Monitoring Sisa Order Realtime Tanpa Risiko Data Rusak
CREATE OR REPLACE VIEW `view_order_shipment_progress` AS
SELECT 
    o.id AS order_id,
    o.po_number,
    o.buyer_name,
    s.id AS shipment_id,
    s.target_delivery_date,
    st.style_code,
    st.color_code,
    st.size,
    s.qty_pairs AS ordered_qty,
    COALESCE(SUM(pb.good_pairs), 0) AS produced_good_qty,
    COALESCE(SUM(pb.defect_pairs), 0) AS produced_defect_qty,
    GREATEST(0, CAST(s.qty_pairs AS SIGNED) - CAST(COALESCE(SUM(pb.good_pairs), 0) AS SIGNED)) AS sisa_pairs,
    ROUND(
        (COALESCE(SUM(pb.good_pairs), 0) / NULLIF(s.qty_pairs, 0)) * 100, 
        2
    ) AS completion_percentage
FROM `orders` o
JOIN `order_styles` st ON st.order_id = o.id
JOIN `order_shipments` s ON s.order_style_id = st.id
LEFT JOIN `production_boxes` pb ON pb.order_shipment_id = s.id
GROUP BY s.id, o.id, st.id;

-- ------------------------------------------------------------------------------
-- 6. INITIAL SEED DATA (ESSENTIAL MASTER)
-- ------------------------------------------------------------------------------

-- Seed Plants
INSERT INTO `plants` (`id`, `code`, `name`, `address`) VALUES
(1, 'PLANT-1', 'Knitting Plant Utama Cikande', 'Kawasan Industri Modern Cikande')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Seed Factory Areas
INSERT INTO `factory_areas` (`id`, `plant_id`, `area_code`, `area_name`, `department_type`) VALUES
(1, 1, 'KK9D', 'Lantai Rajut Gedung 9D', 'KNITTING'),
(2, 1, 'KK8B', 'Lantai Rajut Gedung 8B', 'KNITTING'),
(3, 1, 'ROSSO', 'Departemen Jahit Rosso & Linking', 'LINKING'),
(4, 1, 'SETTING', 'Departemen Boarding & Setting Oven', 'SETTING'),
(5, 1, 'PACKING', 'Departemen Final Inspection & Packing', 'PACKING'),
(6, 1, 'GUDANG', 'Gudang Finished Goods & Benang', 'WAREHOUSE')
ON DUPLICATE KEY UPDATE `area_name`=VALUES(`area_name`);

-- Seed Machine Types
INSERT INTO `machine_types` (`id`, `brand`, `model_name`, `cylinder_inches`, `needle_count`, `standard_speed_rpm`) VALUES
(1, 'LONATI', 'GL616F', 3.50, 144, 280),
(2, 'LONATI', 'GL616F', 3.75, 168, 280),
(3, 'SANTONI', 'HT50', 3.75, 120, 300),
(4, 'DAKONG', 'DK-B304', 3.50, 96, 260)
ON DUPLICATE KEY UPDATE `model_name`=VALUES(`model_name`);

-- Seed Defect Types
INSERT INTO `defect_types` (`id`, `defect_code`, `description`, `category`, `is_reworkable`) VALUES
(1, 'BS-BOLONG', 'Lubang/Bolong pada rajutan kain', 'KNITTING_NEEDLE', TRUE),
(2, 'BS-GARIS', 'Garis vertikal akibat jarum cacat/patah', 'KNITTING_NEEDLE', FALSE),
(3, 'BS-MINYAK', 'Tercemar pelumas oli mesin', 'DIRT_OIL', TRUE),
(4, 'BS-BELANG', 'Perbedaan lot warna/gradasi benang', 'YARN_DEFECT', FALSE),
(5, 'BS-ROSSO', 'Jahitan linking ujung jari lepas/meleset', 'LINKING_DEFECT', TRUE)
ON DUPLICATE KEY UPDATE `description`=VALUES(`description`);

-- Seed Downtime Reasons
INSERT INTO `downtime_reasons` (`id`, `code`, `name`, `category`) VALUES
(1, 'DT-NEEDLE', 'Ganti jarum patah / lidah bengkok', 'MECHANICAL'),
(2, 'DT-TIMING', 'Sinkronisasi timing silinder dan feeder', 'MECHANICAL'),
(3, 'DT-YARN-WAIT', 'Menunggu pasokan benang dari rak lantai', 'WAITING_YARN'),
(4, 'DT-PPS-WAIT', 'Menunggu persetujuan sampel PPS oleh QA/MD', 'WAITING_PPS'),
(5, 'DT-POWER', 'Pemadaman listrik PLN / Genset', 'POWER_OUTAGE')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

SET FOREIGN_KEY_CHECKS = 1;
