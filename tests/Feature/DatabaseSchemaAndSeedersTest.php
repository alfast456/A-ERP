<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaAndSeedersTest extends TestCase
{
    public function test_all_core_tables_exist(): void
    {
        $tables = [
            'plants',
            'factory_areas',
            'users',
            'user_area_access',
            'machine_types',
            'machines',
            'defect_types',
            'downtime_reasons',
            'company_holidays',
            'orders',
            'order_styles',
            'order_shipments',
            'order_revisions',
            'order_revision_items',
            'sample_approvals',
            'planning_allocations',
            'daily_planning_loads',
            'machine_assignments',
            'capacity_forecasts',
            'monthly_targets',
            'order_bookings',
            'order_booking_cancellations',
            'production_boxes',
            'box_defects',
            'rework_tracking',
            'flow_templates',
            'flow_steps',
            'wip_movements',
            'yarn_stock_balances',
            'yarn_transactions',
            'machine_downtimes',
            'machine_service_records',
            'machine_service_checklists',
            'machine_waste_logs',
            'needle_consumptions',
            'maintenance_tickets',
            'maintenance_ticket_replies',
            'audit_logs',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table {$table} should exist in database");
        }
    }

    public function test_master_data_seeded_properly(): void
    {
        $this->assertDatabaseHas('plants', ['code' => 'PLANT-1']);
        $this->assertDatabaseHas('factory_areas', ['area_code' => 'KK9D', 'department_type' => 'KNITTING']);
        $this->assertDatabaseHas('factory_areas', ['area_code' => 'ROSSO', 'department_type' => 'LINKING']);
        $this->assertDatabaseHas('machine_types', ['brand' => 'LONATI', 'needle_count' => 144]);
        $this->assertDatabaseHas('defect_types', ['defect_code' => 'BS-BOLONG', 'is_reworkable' => 1]);
        $this->assertDatabaseHas('downtime_reasons', ['code' => 'DT-NEEDLE', 'category' => 'MECHANICAL']);
        $this->assertDatabaseHas('users', ['username' => 'admin', 'role' => 'ADMIN']);
        $this->assertDatabaseHas('users', ['username' => 'planner', 'role' => 'PLANNER']);
    }

    public function test_order_shipment_progress_view_is_queryable(): void
    {
        $results = DB::table('view_order_shipment_progress')->limit(5)->get();
        $this->assertNotNull($results);
    }

    public function test_semantic_foreign_keys_and_columns_exist(): void
    {
        $this->assertTrue(Schema::hasColumns('box_defects', ['order_shipment_id', 'production_box_id', 'no_box', 'no_label', 'factory_area_id', 'defect_date']));
        $this->assertTrue(Schema::hasColumns('rework_tracking', ['order_shipment_id', 'production_box_id', 'no_box', 'no_label', 'machine_id', 'rework_date']));
        $this->assertTrue(Schema::hasColumns('daily_planning_loads', ['planning_allocation_id', 'plan_date', 'planned_machine_count', 'daily_target_pcs']));
        $this->assertTrue(Schema::hasColumns('machine_assignments', ['planning_allocation_id', 'machine_id', 'order_shipment_id', 'start_time', 'stop_time']));
        $this->assertTrue(Schema::hasColumns('sample_approvals', ['order_style_id', 'order_shipment_id', 'machine_id', 'mechanic_name']));
        $this->assertTrue(Schema::hasColumns('audit_logs', ['user_id', 'actor_name', 'action']));
    }
}
