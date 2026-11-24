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
            'total_ordered_pcs' => 1200,
            'order_date' => '2099-12-31',
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
            'qty_pcs' => 1200,
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
        $this->assertEquals(1500, $shipment->qty_pcs);
        $this->assertEquals(1500, $shipment->qty_pairs);
    }

    public function test_production_monitoring_route_is_accessible(): void
    {
        $response = $this->get(route('production.monitoring'));

        $response->assertStatus(200);
        $response->assertSee('Live Sisa Order & Monitoring Produksi');
        $response->assertSee('Total Sisa Harus Dirajut');
    }

    public function test_machines_index_route_is_accessible(): void
    {
        $response = $this->get(route('machines.index'));

        $response->assertStatus(200);
        $response->assertSee('Inventaris & Telemetri Mesin Rajut', false);
        $response->assertSee('Running / Beroperasi');
    }

    public function test_quality_index_route_is_accessible(): void
    {
        $response = $this->get(route('quality.index'));

        $response->assertStatus(200);
        $response->assertSee('Kendali Mutu, Data Cacat (BS) & Perbaikan', false);
        $response->assertSee('Detail Data Cacat Box');

        $reworkResponse = $this->get(route('quality.index', ['tab' => 'rework']));
        $reworkResponse->assertStatus(200);
        $reworkResponse->assertSee('Siklus Perbaikan & Rework', false);
    }

    public function test_downtimes_index_route_is_accessible(): void
    {
        $response = $this->get(route('downtimes.index'));

        $response->assertStatus(200);
        $response->assertSee('Analisis Downtime & Keandalan Mesin', false);
        $response->assertSee('Top 6 Penyebab Downtime Terbanyak');
    }

    public function test_maintenance_index_and_ticket_creation_is_accessible(): void
    {
        $response = $this->get(route('maintenance.index'));

        $response->assertStatus(200);
        $response->assertSee('Tiket Servis & Pengaduan Mesin', false);
        $response->assertSee('Tiket Open');

        $machine = \App\Models\Machine::first();

        $createResponse = $this->post(route('maintenance.store'), [
            'machine_id' => $machine->id,
            'priority' => 'HIGH',
            'issue_description' => 'Sensor benang putus perlu dikalibrasi ulang segera',
        ]);

        $createResponse->assertRedirect(route('maintenance.index'));
        $createResponse->assertSessionHas('success');

        $this->assertDatabaseHas('maintenance_tickets', [
            'machine_id' => $machine->id,
            'priority' => 'HIGH',
            'issue_description' => 'Sensor benang putus perlu dikalibrasi ulang segera',
        ]);
    }

    public function test_yarn_index_route_is_accessible(): void
    {
        $response = $this->get(route('yarn.index'));

        $response->assertStatus(200);
        $response->assertSee('Saldo & Logistik Benang Lantai Rajut', false);
        $response->assertSee('Total Gulungan (Cones)');
    }

    public function test_capacity_index_route_is_accessible(): void
    {
        $response = $this->get(route('capacity.index'));

        $response->assertStatus(200);
        $response->assertSee('Perencanaan Kapasitas & Beban Mesin', false);
        $response->assertSee('Kapasitas Harian / Mesin');
    }

    public function test_audit_index_route_is_accessible(): void
    {
        $response = $this->get(route('audit.index'));

        $response->assertStatus(200);
        $response->assertSee('Enterprise Audit Trail & Log Sistem', false);
        $response->assertSee('Total Log:');
    }
}
