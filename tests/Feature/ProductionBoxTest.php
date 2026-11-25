<?php

namespace Tests\Feature;

use App\Models\FactoryArea;
use App\Models\Machine;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\OrderStyle;
use App\Models\ProductionBox;
use Tests\TestCase;

class ProductionBoxTest extends TestCase
{
    private function createDependencies(): array
    {
        $shipment = OrderShipment::with('orderStyle.order')->first();
        $machine = Machine::first();
        $area = FactoryArea::first();

        return compact('area', 'machine', 'shipment');
    }

    public function test_production_boxes_index_route_is_accessible(): void
    {
        $response = $this->get(route('production.boxes.index'));
        $response->assertStatus(200);
        $response->assertSee('Lantai Produksi: Output Box Rajut');
    }

    public function test_production_boxes_create_route_is_accessible(): void
    {
        $response = $this->get(route('production.boxes.create'));
        $response->assertStatus(200);
        $response->assertSee('Entri Box Produksi Rajut');
    }

    public function test_store_production_box_successfully(): void
    {
        $deps = $this->createDependencies();

        $response = $this->post(route('production.boxes.store'), [
            'order_shipment_id' => $deps['shipment']->id,
            'machine_id' => $deps['machine']->id,
            'factory_area_id' => $deps['area']->id,
            'production_date' => now()->toDateString(),
            'shift' => 1,
            'no_box' => 'BOX-9999',
            'no_label' => 1,
            'good_pcs' => 240,
            'defect_pcs' => 2,
            'weight_kg' => 4.85,
        ]);

        $response->assertRedirect(route('production.boxes.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('production_boxes', [
            'no_box' => 'BOX-9999',
            'good_pcs' => 240,
            'defect_pcs' => 2,
        ]);
    }

    public function test_production_box_show_and_print_label(): void
    {
        $deps = $this->createDependencies();
        $box = ProductionBox::where('no_box', 'BOX-9999')->first();

        if (!$box) {
            $box = ProductionBox::create([
                'box_barcode' => 'BX-TEST-LABEL-01',
                'no_box' => 'BOX-9999',
                'production_date' => now()->toDateString(),
                'shift' => 1,
                'order_shipment_id' => $deps['shipment']->id,
                'machine_id' => $deps['machine']->id,
                'factory_area_id' => $deps['area']->id,
                'imported_by' => 1,
                'good_pcs' => 240,
                'defect_pcs' => 0,
                'created_at' => now(),
            ]);
        }

        $showResponse = $this->get(route('production.boxes.show', $box));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('BOX-9999');

        $labelResponse = $this->get(route('production.boxes.print-label', $box));
        $labelResponse->assertStatus(200);
        $labelResponse->assertSee('LABEL KONTROL BOX RAJUT');
        $labelResponse->assertSee('BOX-9999');
    }

    public function test_timter_matrix_route_is_accessible(): void
    {
        $response = $this->get(route('production.timter'));
        $response->assertStatus(200);
        $response->assertSee('Timter: Timbang Terus & Akurasi Output', false);
    }
}
