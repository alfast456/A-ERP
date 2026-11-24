<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
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
                s.qty_pairs;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `view_order_shipment_progress`");
    }
};
