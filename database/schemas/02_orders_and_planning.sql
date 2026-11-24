-- ==============================================================================
-- A-ERP Database Schema: 02_orders_and_planning.sql
-- Domain: Order, Style SKU, Delivery Shipments, Revisi, PPS, & Planning Mesin
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Kontrak Induk Order / PDK Pembeli (Orders)
CREATE TABLE IF NOT EXISTS `orders` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `po_number` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Nomor PO / PDK Buyer',
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

-- 2. Detail Varian Style / SKU Produk (Order Styles)
CREATE TABLE IF NOT EXISTS `order_styles` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `machine_type_id` INT UNSIGNED NOT NULL,
    `style_code` VARCHAR(50) NOT NULL,
    `color_code` VARCHAR(30) NOT NULL,
    `size` VARCHAR(20) NOT NULL,
    `smv_seconds` DECIMAL(6,2) NOT NULL DEFAULT 120.00 COMMENT 'SMV detik per pasang',
    `unit_weight_gram` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    `gw_dz` DECIMAL(6,2) NOT NULL DEFAULT 0.00 COMMENT 'Gross Weight per Lusin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_order_styles_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_order_styles_mctype` FOREIGN KEY (`machine_type_id`) REFERENCES `machine_types` (`id`) ON DELETE RESTRICT,
    INDEX `idx_style_lookup` (`style_code`, `color_code`, `size`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Jadwal Pengiriman Multi-Delivery (Order Shipments)
-- Inovasi: Memisahkan delivery dari style_id sehingga reschedule tidak memecah baris order!
CREATE TABLE IF NOT EXISTS `order_shipments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_style_id` BIGINT UNSIGNED NOT NULL,
    `shipment_index` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `target_delivery_date` DATE NOT NULL,
    `qty_pairs` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_order_shipments_style` FOREIGN KEY (`order_style_id`) REFERENCES `order_styles` (`id`) ON DELETE CASCADE,
    INDEX `idx_shipment_delivery` (`target_delivery_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Log Audit Pengajuan Revisi Order (Order Revisions)
CREATE TABLE IF NOT EXISTS `order_revisions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `revision_number` SMALLINT UNSIGNED NOT NULL,
    `revision_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `reason_notes` TEXT NOT NULL,
    `requested_by` BIGINT UNSIGNED NOT NULL,
    `approved_by` BIGINT UNSIGNED NULL,
    `status` ENUM('PENDING', 'APPROVED', 'REJECTED') NOT NULL DEFAULT 'APPROVED',
    CONSTRAINT `fk_revisions_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_revisions_req_user` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_revisions_app_user` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    UNIQUE KEY `uk_order_rev_no` (`order_id`, `revision_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Detail Delta Penurunan / Penambahan Qty Revisi (Order Revision Items)
-- Otomatis mencatat cancel order dan pengurangan qty tanpa kehilangan relasi
CREATE TABLE IF NOT EXISTS `order_revision_items` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_revision_id` BIGINT UNSIGNED NOT NULL,
    `order_shipment_id` BIGINT UNSIGNED NOT NULL,
    `old_qty` INT UNSIGNED NOT NULL,
    `new_qty` INT UNSIGNED NOT NULL,
    `delta_qty` INT NOT NULL COMMENT 'Jika negatif = cancel/penurunan, jika positif = penambahan',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_rev_items_revision` FOREIGN KEY (`order_revision_id`) REFERENCES `order_revisions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rev_items_shipment` FOREIGN KEY (`order_shipment_id`) REFERENCES `order_shipments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Gatekeeper Approval Sampel Pre-Production (Sample Approvals / PPS)
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
    CONSTRAINT `fk_pps_style` FOREIGN KEY (`order_style_id`) REFERENCES `order_styles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pps_machine` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_pps_mechanic` FOREIGN KEY (`mechanic_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_pps_qa` FOREIGN KEY (`qa_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_pps_md` FOREIGN KEY (`merchandiser_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Alokasi Beban Order ke Area Pabrik (Planning Allocations)
CREATE TABLE IF NOT EXISTS `planning_allocations` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `factory_area_id` INT UNSIGNED NOT NULL,
    `allocated_pairs` INT UNSIGNED NOT NULL,
    `target_start_date` DATE NOT NULL,
    `target_end_date` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_plan_alloc_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_plan_alloc_area` FOREIGN KEY (`factory_area_id`) REFERENCES `factory_areas` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Jadwal Alokasi Fisik Mesin Harian (Machine Assignments)
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
    CONSTRAINT `fk_mc_assign_alloc` FOREIGN KEY (`planning_allocation_id`) REFERENCES `planning_allocations` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_mc_assign_machine` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_mc_assign_shipment` FOREIGN KEY (`order_shipment_id`) REFERENCES `order_shipments` (`id`) ON DELETE CASCADE,
    INDEX `idx_plan_machine_date` (`machine_id`, `plan_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Perhitungan Beban Kapasitas IE (Capacity Forecasts)
CREATE TABLE IF NOT EXISTS `capacity_forecasts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `factory_area_id` INT UNSIGNED NOT NULL,
    `machine_type_id` INT UNSIGNED NOT NULL,
    `forecast_period` VARCHAR(7) NOT NULL COMMENT 'Format YYYY-MM',
    `active_machines_count` INT UNSIGNED NOT NULL,
    `available_hours` DECIMAL(10,2) NOT NULL,
    `required_hours` DECIMAL(10,2) NOT NULL,
    `load_percentage` DECIMAL(5,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_cap_area` FOREIGN KEY (`factory_area_id`) REFERENCES `factory_areas` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cap_mctype` FOREIGN KEY (`machine_type_id`) REFERENCES `machine_types` (`id`) ON DELETE RESTRICT,
    UNIQUE KEY `uk_cap_period_type` (`factory_area_id`, `machine_type_id`, `forecast_period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Target Ekspor & Efisiensi Bulanan (Monthly Targets)
CREATE TABLE IF NOT EXISTS `monthly_targets` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `plant_id` INT UNSIGNED NOT NULL,
    `target_month` VARCHAR(7) NOT NULL COMMENT 'Format YYYY-MM',
    `target_export_pairs` INT UNSIGNED NOT NULL DEFAULT 0,
    `target_local_pairs` INT UNSIGNED NOT NULL DEFAULT 0,
    `target_efficiency` DECIMAL(5,2) NOT NULL DEFAULT 85.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_targets_plant` FOREIGN KEY (`plant_id`) REFERENCES `plants` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_target_plant_month` (`plant_id`, `target_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Antrean Booking Order (Order Bookings)
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
    CONSTRAINT `fk_booking_mctype` FOREIGN KEY (`machine_type_id`) REFERENCES `machine_types` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
