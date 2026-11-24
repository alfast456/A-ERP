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
            'machine_assignments',
            'capacity_forecasts',
            'monthly_targets',
            'order_bookings',
            'production_boxes',
            'box_defects',
            'rework_tracking',
            'flow_templates',
            'flow_steps',
            'wip_movements',
            'yarn_stock_balances',
            'yarn_transactions',
            'machine_downtimes',
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
        $results = DB::table('view_order_shipment_progress')->get();
        $this->assertNotNull($results);
    }
}
