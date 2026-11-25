<?php

namespace Tests\Feature;

use App\Enums\WipStatus;
use App\Models\FactoryArea;
use App\Models\Machine;
use App\Models\OrderShipment;
use App\Models\ProductionBox;
use App\Models\WipMovement;
use Tests\TestCase;

class WipMovementTest extends TestCase
{
    private function createBox(): ProductionBox
    {
        $area = FactoryArea::first();
        $machine = Machine::first();
        $shipment = OrderShipment::first();

        $userId = \App\Models\User::first()?->id ?? 1;

        return ProductionBox::create([
            'box_barcode' => 'BX-WIP-TEST-' . rand(1000, 9999),
            'no_box' => 'WIP-' . rand(100, 999),
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

    public function test_wip_index_route_is_accessible(): void
    {
        $response = $this->get(route('wip.index'));
        $response->assertStatus(200);
        $response->assertSee('Alur WIP & Mutasi Antar-Departemen', false);
    }

    public function test_wip_transfer_create_route_is_accessible(): void
    {
        $response = $this->get(route('wip.transfer.create'));
        $response->assertStatus(200);
        $response->assertSee('Kirim Transfer Box WIP');
    }

    public function test_store_wip_transfer_successfully(): void
    {
        $box = $this->createBox();
        $areas = FactoryArea::take(2)->get();

        if ($areas->count() < 2) {
            $fromArea = $areas->first();
            $toArea = FactoryArea::create(['plant_id' => 1, 'area_code' => 'TO_AREA', 'area_name' => 'Rosso Line 2']);
        } else {
            $fromArea = $areas[0];
            $toArea = $areas[1];
        }

        $response = $this->post(route('wip.transfer.store'), [
            'production_box_id' => $box->id,
            'from_area_id' => $fromArea->id,
            'to_area_id' => $toArea->id,
            'movement_date' => now()->toDateString(),
            'sent_pcs' => 240,
            'remarks' => 'Transfer uji coba ke Rosso',
        ]);

        $response->assertRedirect(route('wip.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('wip_movements', [
            'production_box_id' => $box->id,
            'from_area_id' => $fromArea->id,
            'to_area_id' => $toArea->id,
            'sent_pcs' => 240,
            'status' => 'DISPATCHED',
        ]);
    }

    public function test_receive_wip_box_workflow(): void
    {
        $box = $this->createBox();
        $areas = FactoryArea::take(2)->get();
        $fromArea = $areas[0];
        $toArea = $areas->count() > 1 ? $areas[1] : $fromArea;

        $movement = WipMovement::create([
            'movement_date' => now()->toDateString(),
            'production_box_id' => $box->id,
            'from_area_id' => $fromArea->id,
            'to_area_id' => $toArea->id,
            'sent_pcs' => 240,
            'received_pcs' => 0,
            'dispatched_by' => \App\Models\User::first()?->id ?? 1,
            'status' => WipStatus::DISPATCHED,
            'created_at' => now(),
        ]);

        // 1. Terima utuh
        $response = $this->post(route('wip.receive', $movement), [
            'received_pcs' => 240,
            'remarks' => 'Diterima lengkap',
        ]);

        $response->assertSessionHas('success');
        $movement->refresh();
        $this->assertEquals(WipStatus::RECEIVED, $movement->status);
        $this->assertEquals(240, $movement->received_pcs);

        // 2. Terima dengan selisih
        $this->post(route('wip.receive', $movement), [
            'received_pcs' => 238,
            'remarks' => 'Kurang 2 pcs',
        ]);

        $movement->refresh();
        $this->assertEquals(WipStatus::DISCREPANCY, $movement->status);
        $this->assertEquals(238, $movement->received_pcs);
    }

    public function test_wip_buffer_kanban_route_is_accessible(): void
    {
        $response = $this->get(route('wip.buffer'));
        $response->assertStatus(200);
        $response->assertSee('Papan Buffer & Staging Area WIP', false);
    }

    public function test_flow_templates_crud_workflow(): void
    {
        $response = $this->get(route('wip.flows.index'));
        $response->assertStatus(200);
        $response->assertSee('Master Template Alur Rute WIP');

        $createResponse = $this->post(route('wip.flows.store'), [
            'name' => 'Alur Ankle Standar Test',
            'description' => 'Test flow',
            'steps' => ['KNITTING', 'LINKING', 'SETTING', 'PACKING', 'WAREHOUSE'],
        ]);

        $createResponse->assertRedirect(route('wip.flows.index'));
        $createResponse->assertSessionHas('success');

        $this->assertDatabaseHas('flow_templates', [
            'name' => 'Alur Ankle Standar Test',
        ]);
    }
}
