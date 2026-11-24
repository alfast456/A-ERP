<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\MachineStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\AuditLog;
use App\Models\FactoryArea;
use App\Models\Machine;
use App\Models\MachineType;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\OrderStyle;
use App\Models\Plant;
use App\Models\ProductionBox;
use App\Models\User;
use App\Models\YarnStockBalance;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EloquentModelsAndEnumsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_order_and_shipment_dynamic_sisa_kalkulasi(): void
    {
        $plant = Plant::first();
        $area = FactoryArea::where('department_type', DepartmentType::KNITTING)->first();
        $machineType = MachineType::first();

        $machine = Machine::create([
            'machine_type_id' => $machineType->id,
            'factory_area_id' => $area->id,
            'machine_number' => 'TEST-MC-999',
            'status' => MachineStatus::RUNNING,
        ]);

        $user = User::where('username', 'admin')->first();

        // 1. Buat Order
        $order = Order::create([
            'po_number' => 'PO-TEST-001',
            'buyer_name' => 'NIKE GLOBAL',
            'season' => 'FA26',
            'order_type' => OrderType::BULK,
            'total_ordered_pcs' => 1000,
            'order_date' => now()->toDateString(),
            'status' => OrderStatus::IN_PRODUCTION,
        ]);

        $this->assertEquals(OrderStatus::IN_PRODUCTION, $order->status);
        $this->assertEquals(1000, $order->total_ordered_pcs);
        $this->assertEquals(1000, $order->total_ordered_pairs);

        // 2. Buat OrderStyle
        $style = OrderStyle::create([
            'order_id' => $order->id,
            'machine_type_id' => $machineType->id,
            'style_code' => 'SX-TEST-1',
            'color_code' => 'BLK',
            'size' => 'L',
            'smv_seconds' => 110.50,
            'unit_weight_gram' => 45.20,
            'gw_dz' => 540.00,
        ]);

        // 3. Buat Shipment (Order 1.000 pcs)
        $shipment = OrderShipment::create([
            'order_style_id' => $style->id,
            'shipment_index' => 1,
            'target_delivery_date' => now()->addDays(14)->toDateString(),
            'qty_pcs' => 1000,
        ]);

        // Awalnya sisa harus 1.000 dan completion 0%
        $this->assertEquals(0, $shipment->produced_good_pcs);
        $this->assertEquals(0, $shipment->produced_good_pairs);
        $this->assertEquals(1000, $shipment->sisa_pcs);
        $this->assertEquals(1000, $shipment->sisa_pairs);
        $this->assertEquals(0.0, $shipment->completion_percentage);

        // 4. Input Output Box 1: 400 pcs bagus, 10 defect
        ProductionBox::create([
            'box_barcode' => 'BOX-TEST-001',
            'production_date' => now()->toDateString(),
            'shift' => 1,
            'order_shipment_id' => $shipment->id,
            'machine_id' => $machine->id,
            'operator_id' => $user->id,
            'good_pcs' => 400,
            'defect_pcs' => 10,
            'weight_kg' => 18.200,
        ]);

        // Sisa sekarang harus 600, completion 40%
        $this->assertEquals(400, $shipment->produced_good_pcs);
        $this->assertEquals(400, $shipment->produced_good_pairs);
        $this->assertEquals(10, $shipment->produced_defect_pcs);
        $this->assertEquals(10, $shipment->produced_defect_pairs);
        $this->assertEquals(600, $shipment->sisa_pcs);
        $this->assertEquals(600, $shipment->sisa_pairs);
        $this->assertEquals(40.0, $shipment->completion_percentage);

        // 5. Input Output Box 2: 600 pcs bagus
        ProductionBox::create([
            'box_barcode' => 'BOX-TEST-002',
            'production_date' => now()->toDateString(),
            'shift' => 2,
            'order_shipment_id' => $shipment->id,
            'machine_id' => $machine->id,
            'operator_id' => $user->id,
            'good_pcs' => 600,
            'defect_pcs' => 5,
            'weight_kg' => 27.150,
        ]);

        // Sisa sekarang harus 0 dan completion 100%
        $this->assertEquals(1000, $shipment->produced_good_pcs);
        $this->assertEquals(1000, $shipment->produced_good_pairs);
        $this->assertEquals(0, $shipment->sisa_pcs);
        $this->assertEquals(0, $shipment->sisa_pairs);
        $this->assertEquals(100.0, $shipment->completion_percentage);
    }

    public function test_yarn_stock_balance_and_audit_log_relations(): void
    {
        $area = FactoryArea::first();
        $user = User::first();

        $yarn = YarnStockBalance::create([
            'factory_area_id' => $area->id,
            'yarn_code' => 'YRN-TC-30S',
            'yarn_name' => 'TC 30s Black',
            'color_name' => 'Black',
            'lot_number' => 'LOT-2026-01',
            'qty_cones' => 120,
            'weight_kg' => 240.500,
        ]);

        $this->assertEquals(120, $yarn->qty_cones);

        $log = AuditLog::create([
            'user_id' => $user->id,
            'action' => 'CREATE',
            'table_name' => 'yarn_stock_balances',
            'record_id' => $yarn->id,
            'old_values' => null,
            'new_values' => ['qty_cones' => 120],
        ]);

        $this->assertIsArray($log->new_values);
        $this->assertEquals(120, $log->new_values['qty_cones']);
        $this->assertEquals($user->id, $log->user->id);
    }
}
