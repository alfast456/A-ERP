<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\MachineType;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\OrderStyle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserInterfaceRoutesTest extends TestCase
{
    use DatabaseTransactions;

    private function createOrderWithShipment(): Order
    {
        $order = Order::create([
            'po_number' => 'PO-UI-TEST-'.uniqid(),
            'buyer_name' => 'NIKE EUROPE',
            'season' => 'SP26',
            'order_type' => OrderType::BULK,
            'total_ordered_pairs' => 1200,
            'order_date' => now()->toDateString(),
            'status' => OrderStatus::IN_PRODUCTION,
        ]);

        $style = OrderStyle::create([
            'order_id' => $order->id,
            'machine_type_id' => MachineType::first()->id,
            'style_code' => 'SX-CREW-UI',
            'color_code' => 'BLK',
            'size' => 'XL',
        ]);

        OrderShipment::create([
            'order_style_id' => $style->id,
            'shipment_index' => 1,
            'target_delivery_date' => now()->addDays(14)->toDateString(),
            'qty_pairs' => 1200,
        ]);

        return $order;
    }

    public function test_dashboard_route_is_accessible(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Executive Manufacturing Dashboard');
        $response->assertSee('Kontrak Order (PO)');
    }

    public function test_orders_index_route_is_accessible(): void
    {
        $order = $this->createOrderWithShipment();

        $response = $this->get(route('orders.index'));

        $response->assertStatus(200);
        $response->assertSee('Manajemen Order & Revisi');
        $response->assertSee($order->po_number);
    }

    public function test_orders_show_route_is_accessible(): void
    {
        $order = $this->createOrderWithShipment();

        $response = $this->get(route('orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee($order->po_number);
        $response->assertSee('Pengajuan Revisi Kuantitas Order');
    }

    public function test_orders_revise_action_submits_successfully(): void
    {
        $order = $this->createOrderWithShipment();
        $shipment = $order->orderStyles->first()->orderShipments->first();

        $response = $this->post(route('orders.revise', $order), [
            'reason_notes' => 'Penyesuaian kuantitas dari buyer',
            'items' => [
                [
                    'shipment_id' => $shipment->id,
                    'new_qty' => 1500,
                ],
            ],
        ]);

        $response->assertRedirect(route('orders.show', $order));
        $response->assertSessionHas('success');

        $shipment->refresh();
        $this->assertEquals(1500, $shipment->qty_pairs);
    }

    public function test_production_monitoring_route_is_accessible(): void
    {
        $response = $this->get(route('production.monitoring'));

        $response->assertStatus(200);
        $response->assertSee('Live Sisa Order & Monitoring Produksi');
        $response->assertSee('Total Sisa Harus Dirajut');
    }
}
