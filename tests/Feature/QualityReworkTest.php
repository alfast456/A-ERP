<?php

namespace Tests\Feature;

use App\Enums\ReworkStatus;
use App\Models\DefectType;
use App\Models\FactoryArea;
use App\Models\Machine;
use App\Models\OrderShipment;
use App\Models\ProductionBox;
use App\Models\ReworkTracking;
use Tests\TestCase;

class QualityReworkTest extends TestCase
{
    private function createBox(): ProductionBox
    {
        $area = FactoryArea::first();
        $machine = Machine::first();
        $shipment = OrderShipment::first();
        $userId = \App\Models\User::first()?->id ?? 1;

        return ProductionBox::create([
            'box_barcode' => 'BX-QC-TEST-' . rand(1000, 9999),
            'no_box' => 'QC-' . rand(100, 999),
            'production_date' => now()->toDateString(),
            'shift' => 1,
            'order_shipment_id' => $shipment->id,
            'machine_id' => $machine->id,
            'factory_area_id' => $area->id,
            'imported_by' => $userId,
            'good_pcs' => 240,
            'defect_pcs' => 0,
            'created_at' => now(),
        ]);
    }

    public function test_quality_defects_create_route_is_accessible(): void
    {
        $response = $this->get(route('quality.defects.create'));
        $response->assertStatus(200);
        $response->assertSee('Lapor Temuan Cacat (BS)', false);
    }

    public function test_store_box_defect_with_rework_trigger(): void
    {
        $box = $this->createBox();
        $defectType = DefectType::first() ?? DefectType::create([
            'defect_code' => 'D-LUBANG',
            'description' => 'Lubang Tumit Test',
            'category' => 'KNITTING_NEEDLE',
        ]);

        $response = $this->post(route('quality.defects.store'), [
            'production_box_id' => $box->id,
            'defect_type_id' => $defectType->id,
            'defect_pcs' => 4,
            'notes' => 'Defect jarum patah test',
            'create_rework' => 1,
        ]);

        $response->assertRedirect(route('quality.index', ['tab' => 'defects']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('box_defects', [
            'production_box_id' => $box->id,
            'defect_type_id' => $defectType->id,
            'defect_count_pcs' => 4,
        ]);

        $this->assertDatabaseHas('rework_tracking', [
            'production_box_id' => $box->id,
            'defect_type_id' => $defectType->id,
            'sent_pcs' => 4,
            'status' => 'IN_REPAIR',
        ]);
    }

    public function test_resolve_rework_workflow(): void
    {
        $box = $this->createBox();
        $defectType = DefectType::first();

        $rework = ReworkTracking::create([
            'order_shipment_id' => $box->order_shipment_id,
            'production_box_id' => $box->id,
            'defect_type_id' => $defectType->id,
            'sent_pcs' => 5,
            'repaired_pcs' => 0,
            'scrapped_pcs' => 0,
            'status' => ReworkStatus::IN_REPAIR,
            'repair_area_id' => $box->factory_area_id,
            'rework_date' => now()->toDateString(),
            'created_at' => now(),
        ]);

        // 1. Resolve successfully (kaos kaki berhasil diperbaiki)
        $resolveResponse = $this->post(route('quality.rework.resolve', $rework), [
            'action_type' => 'RESOLVE',
            'qty' => 5,
        ]);

        $resolveResponse->assertRedirect(route('quality.index', ['tab' => 'rework']));
        $resolveResponse->assertSessionHas('success');

        $rework->refresh();
        $this->assertEquals(ReworkStatus::RESOLVED, $rework->status);
        $this->assertEquals(5, $rework->repaired_pcs);

        // 2. Scrap test
        $rework2 = ReworkTracking::create([
            'order_shipment_id' => $box->order_shipment_id,
            'production_box_id' => $box->id,
            'defect_type_id' => $defectType->id,
            'sent_pcs' => 3,
            'repaired_pcs' => 0,
            'scrapped_pcs' => 0,
            'status' => ReworkStatus::IN_REPAIR,
            'repair_area_id' => $box->factory_area_id,
            'rework_date' => now()->toDateString(),
            'created_at' => now(),
        ]);

        $scrapResponse = $this->post(route('quality.rework.resolve', $rework2), [
            'action_type' => 'SCRAP',
            'qty' => 3,
        ]);

        $scrapResponse->assertSessionHas('success');
        $rework2->refresh();
        $this->assertEquals(ReworkStatus::SCRAPPED, $rework2->status);
        $this->assertEquals(3, $rework2->scrapped_pcs);
    }

    public function test_machine_waste_log_workflow(): void
    {
        $response = $this->get(route('quality.waste'));
        $response->assertStatus(200);
        $response->assertSee('Log Limbah Mesin & Waste Rajut', false);

        $machine = Machine::first();
        $area = FactoryArea::first();

        $createResponse = $this->post(route('quality.waste.store'), [
            'production_date' => now()->toDateString(),
            'machine_id' => $machine->id,
            'factory_area_id' => $area->id,
            'shift' => 1,
            'qty_pcs' => 2,
            'weight_gram' => 85.5,
            'employee_name' => 'Budi Santoso',
        ]);

        $createResponse->assertRedirect(route('quality.waste'));
        $createResponse->assertSessionHas('success');

        $this->assertDatabaseHas('machine_waste_logs', [
            'machine_id' => $machine->id,
            'weight_gram' => 85.5,
            'employee_name' => 'Budi Santoso',
        ]);
    }
}
