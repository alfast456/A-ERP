<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rename columns across physical and planning tables
        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('total_ordered_pairs', 'total_ordered_pcs');
        });

        Schema::table('order_shipments', function (Blueprint $table) {
            $table->renameColumn('qty_pairs', 'qty_pcs');
        });

        Schema::table('planning_allocations', function (Blueprint $table) {
            $table->renameColumn('allocated_pairs', 'allocated_pcs');
        });

        Schema::table('machine_assignments', function (Blueprint $table) {
            $table->renameColumn('target_pairs', 'target_pcs');
        });

        Schema::table('monthly_targets', function (Blueprint $table) {
            $table->renameColumn('target_export_pairs', 'target_export_pcs');
            $table->renameColumn('target_local_pairs', 'target_local_pcs');
        });

        Schema::table('order_bookings', function (Blueprint $table) {
            $table->renameColumn('estimated_pairs', 'estimated_pcs');
        });

        Schema::table('production_boxes', function (Blueprint $table) {
            $table->renameColumn('good_pairs', 'good_pcs');
            $table->renameColumn('defect_pairs', 'defect_pcs');
        });

        Schema::table('box_defects', function (Blueprint $table) {
            $table->renameColumn('defect_count_pairs', 'defect_count_pcs');
        });

        Schema::table('rework_tracking', function (Blueprint $table) {
            $table->renameColumn('sent_pairs', 'sent_pcs');
            $table->renameColumn('repaired_pairs', 'repaired_pcs');
            $table->renameColumn('scrapped_pairs', 'scrapped_pcs');
        });

        Schema::table('wip_movements', function (Blueprint $table) {
            $table->renameColumn('sent_pairs', 'sent_pcs');
            $table->renameColumn('received_pairs', 'received_pcs');
        });

        // 2. Re-create view_order_shipment_progress with PCS columns
        DB::statement('
            CREATE OR REPLACE VIEW `view_order_shipment_progress` AS
            SELECT 
                o.id AS order_id,
                o.po_number,
                o.buyer_name,
                s.id AS shipment_id,
                s.target_delivery_date,
                st.style_code,
                st.inisial,
                st.color_code,
                st.size,
                s.qty_pcs AS ordered_qty,
                COALESCE(SUM(pb.good_pcs), 0) AS produced_good_qty,
                COALESCE(SUM(pb.defect_pcs), 0) AS produced_defect_qty,
                GREATEST(0, CAST(s.qty_pcs AS SIGNED) - CAST(COALESCE(SUM(pb.good_pcs), 0) AS SIGNED)) AS sisa_pcs,
                ROUND(
                    (COALESCE(SUM(pb.good_pcs), 0) / NULLIF(s.qty_pcs, 0)) * 100, 
                    2
                ) AS completion_percentage
            FROM `orders` o
            JOIN `order_styles` st ON st.order_id = o.id
            JOIN `order_shipments` s ON s.order_style_id = st.id
            LEFT JOIN `production_boxes` pb ON pb.order_shipment_id = s.id
            GROUP BY 
                s.id, 
                o.id, 
                o.po_number, 
                o.buyer_name, 
                s.target_delivery_date, 
                st.id, 
                st.style_code, 
                st.inisial, 
                st.color_code, 
                st.size, 
                s.qty_pcs;
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('
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
                s.qty_pcs AS ordered_qty,
                COALESCE(SUM(pb.good_pcs), 0) AS produced_good_qty,
                COALESCE(SUM(pb.defect_pcs), 0) AS produced_defect_qty,
                GREATEST(0, CAST(s.qty_pcs AS SIGNED) - CAST(COALESCE(SUM(pb.good_pcs), 0) AS SIGNED)) AS sisa_pairs,
                ROUND(
                    (COALESCE(SUM(pb.good_pcs), 0) / NULLIF(s.qty_pcs, 0)) * 100, 
                    2
                ) AS completion_percentage
            FROM `orders` o
            JOIN `order_styles` st ON st.order_id = o.id
            JOIN `order_shipments` s ON s.order_style_id = st.id
            LEFT JOIN `production_boxes` pb ON pb.order_shipment_id = s.id
            GROUP BY 
                s.id, 
                o.id, 
                o.po_number, 
                o.buyer_name, 
                s.target_delivery_date, 
                st.id, 
                st.style_code, 
                st.color_code, 
                st.size, 
                s.qty_pcs;
        ');

        Schema::table('wip_movements', function (Blueprint $table) {
            $table->renameColumn('received_pcs', 'received_pairs');
            $table->renameColumn('sent_pcs', 'sent_pairs');
        });

        Schema::table('rework_tracking', function (Blueprint $table) {
            $table->renameColumn('scrapped_pcs', 'scrapped_pairs');
            $table->renameColumn('repaired_pcs', 'repaired_pairs');
            $table->renameColumn('sent_pcs', 'sent_pairs');
        });

        Schema::table('box_defects', function (Blueprint $table) {
            $table->renameColumn('defect_count_pcs', 'defect_count_pairs');
        });

        Schema::table('production_boxes', function (Blueprint $table) {
            $table->renameColumn('defect_pcs', 'defect_pairs');
            $table->renameColumn('good_pcs', 'good_pairs');
        });

        Schema::table('order_bookings', function (Blueprint $table) {
            $table->renameColumn('estimated_pcs', 'estimated_pairs');
        });

        Schema::table('monthly_targets', function (Blueprint $table) {
            $table->renameColumn('target_local_pcs', 'target_local_pairs');
            $table->renameColumn('target_export_pcs', 'target_export_pairs');
        });

        Schema::table('machine_assignments', function (Blueprint $table) {
            $table->renameColumn('target_pcs', 'target_pairs');
        });

        Schema::table('planning_allocations', function (Blueprint $table) {
            $table->renameColumn('allocated_pcs', 'allocated_pairs');
        });

        Schema::table('order_shipments', function (Blueprint $table) {
            $table->renameColumn('qty_pcs', 'qty_pairs');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('total_ordered_pcs', 'total_ordered_pairs');
        });
    }
};
