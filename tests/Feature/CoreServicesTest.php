<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\MachineStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Exceptions\ZeroProductionLossException;
use App\Models\DefectType;
use App\Models\FactoryArea;
use App\Models\Machine;
use App\Models\MachineType;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\OrderStyle;
use App\Models\ProductionBox;
use App\Models\User;
use App\Services\CapacityPlanningService;
use App\Services\OrderReviseService;
use App\Services\ProductionBoxService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CoreServicesTest extends TestCase
{
    use DatabaseTransactions;
    private function createTestMachine(): Machine
    {
        return Machine::firstOrCreate([
            'machine_number' => 'MC-TEST-AUT',
        ], [
            'machine_type_id' => MachineType::first()->id,
            'factory_area_id' => FactoryArea::first()->id,
            'status' => MachineStatus::RUNNING,
        ]);
    }

    private function createTestShipment(): OrderShipment
    {
        $order = Order::create([
            'po_number' => 'PO-HELPER-' . uniqid(),
            'buyer_name' => 'BUYER-TEST',
            'order_date' => now()->toDateString(),
            'status' => OrderStatus::IN_PRODUCTION,
        ]);

        $style = OrderStyle::create([
            'order_id' => $order->id,
            'machine_type_id' => MachineType::first()->id,
            'style_code' => 'STYLE-TEST',
            'color_code' => 'BLK',
            'size' => 'L',
        ]);

        return OrderShipment::create([
            'order_style_id' => $style->id,
            'shipment_index' => 1,
            'target_delivery_date' => now()->addDays(7)->toDateString(),
            'qty_pairs' => 1000,
        ]);
    }

    public function test_capacity_planning_service_calculations(): void
    {
        $service = new CapacityPlanningService();

        // Target: 100.000 pasang, SMV: 120 detik, 25 hari kerja, 24 jam/hari, 85% efisiensi
        // 100.000 * 120 = 12.000.000 detik kebutuhan
        // 1 mesin = 25 * 24 * 3600 * 0.85 = 1.836.000 detik kapasitas
        // Kebutuhan = ceil(12.000.000 / 1.836.000) = 7 unit mesin
        $requiredMachines = $service->calculateRequiredMachines(100000, 120.0, 25, 24.0, 0.85);
        $this->assertEquals(7, $requiredMachines);

        $loadPercentage = $service->calculateLoadPercentage(7, 100000, 120.0, 25, 24.0, 0.85);
        $this->assertEquals(93.37, $loadPercentage);
    }

    public function test_production_box_service_records_output_and_defects_atomically(): void
    {
        $service = new ProductionBoxService();
        $machine = $this->createTestMachine();
        $user = User::first();
        $shipment = $this->createTestShipment();
        $defectType = DefectType::first();

        $box = $service->recordOutput([
            'order_shipment_id' => $shipment->id,
            'machine_id' => $machine->id,
            'operator_id' => $user->id,
            'production_date' => now()->toDateString(),
            'shift' => 1,
            'good_pairs' => 300,
            'defect_pairs' => 8,
            'weight_kg' => 12.500,
        ], [
            [
                'defect_type_id' => $defectType->id,
                'defect_count_pairs' => 8,
                'notes' => 'Lubang pada ujung tumit',
            ],
        ]);

        $this->assertNotNull($box->id);
        $this->assertStringStartsWith('BOX-', $box->box_barcode);
        $this->assertEquals(300, $box->good_pairs);
        $this->assertCount(1, $box->defects);
        $this->assertEquals(8, $box->defects->first()->defect_count_pairs);
    }

    public function test_order_revise_service_increases_quantity_and_creates_revision_item(): void
    {
        $service = new OrderReviseService();
        $user = User::first();

        $order = Order::create([
            'po_number' => 'PO-REV-TEST-' . uniqid(),
            'buyer_name' => 'ADIDAS',
            'season' => 'SP26',
            'order_type' => OrderType::BULK,
            'total_ordered_pairs' => 500,
            'order_date' => now()->toDateString(),
            'status' => OrderStatus::CONFIRMED,
        ]);

        $style = OrderStyle::create([
            'order_id' => $order->id,
            'machine_type_id' => MachineType::first()->id,
            'style_code' => 'ADI-CREW-1',
            'color_code' => 'WHT',
            'size' => 'M',
        ]);

        $shipment = OrderShipment::create([
            'order_style_id' => $style->id,
            'shipment_index' => 1,
            'target_delivery_date' => now()->addDays(20)->toDateString(),
            'qty_pairs' => 500,
        ]);

        // Revisi kenaikan dari 500 -> 750
        $revision = $service->applyRevision($order, [
            [
                'shipment_id' => $shipment->id,
                'new_qty' => 750,
            ],
        ], $user, $user, 'Penambahan repeat order dari buyer');

        $this->assertEquals(1, $revision->revision_number);
        $this->assertCount(1, $revision->items);

        $revItem = $revision->items->first();
        $this->assertEquals(500, $revItem->old_qty);
        $this->assertEquals(750, $revItem->new_qty);
        $this->assertEquals(250, $revItem->delta_qty);

        $shipment->refresh();
        $this->assertEquals(750, $shipment->qty_pairs);

        $order->refresh();
        $this->assertEquals(750, $order->total_ordered_pairs);
    }

    public function test_order_revise_service_zero_production_loss_protection(): void
    {
        $service = new OrderReviseService();
        $user = User::first();
        $machine = $this->createTestMachine();

        $order = Order::create([
            'po_number' => 'PO-REV-FAIL-' . uniqid(),
            'buyer_name' => 'PUMA',
            'season' => 'SP26',
            'order_type' => OrderType::BULK,
            'total_ordered_pairs' => 1000,
            'order_date' => now()->toDateString(),
            'status' => OrderStatus::IN_PRODUCTION,
        ]);

        $style = OrderStyle::create([
            'order_id' => $order->id,
            'machine_type_id' => MachineType::first()->id,
            'style_code' => 'PUMA-ANKLE-1',
            'color_code' => 'RED',
            'size' => 'S',
        ]);

        $shipment = OrderShipment::create([
            'order_style_id' => $style->id,
            'shipment_index' => 1,
            'target_delivery_date' => now()->addDays(10)->toDateString(),
            'qty_pairs' => 1000,
        ]);

        // Mesin sudah merajut 600 pasang
        ProductionBox::create([
            'box_barcode' => 'BOX-PUMA-' . uniqid(),
            'production_date' => now()->toDateString(),
            'shift' => 1,
            'order_shipment_id' => $shipment->id,
            'machine_id' => $machine->id,
            'operator_id' => $user->id,
            'good_pairs' => 600,
            'defect_pairs' => 0,
            'weight_kg' => 25.000,
        ]);

        // User mencoba memangkas order menjadi 400 pasang (di bawah 600 pasang yang sudah jadi)
        $this->expectException(ZeroProductionLossException::class);

        $service->applyRevision($order, [
            [
                'shipment_id' => $shipment->id,
                'new_qty' => 400,
            ],
        ], $user, $user, 'Mencoba memangkas di bawah barang riil');
    }
}
