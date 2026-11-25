<?php

namespace Tests\Feature;

use App\Models\FactoryArea;
use App\Models\MachineType;
use App\Models\MonthlyTarget;
use App\Models\OrderBooking;
use App\Models\Plant;
use App\Models\User;
use Tests\TestCase;

class OrderBookingAndReportsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $user = User::first();
        if ($user) {
            $this->actingAs($user);
        }
    }

    public function test_bookings_index_route_is_accessible(): void
    {
        $response = $this->get(route('bookings.index'));
        $response->assertOk();
        $response->assertSee('Pre-Order Capacity Bookings');
    }

    public function test_bookings_create_route_is_accessible(): void
    {
        $response = $this->get(route('bookings.create'));
        $response->assertOk();
        $response->assertSee('Form Pre-Order Booking Kapasitas');
    }

    public function test_store_order_booking(): void
    {
        $machineType = MachineType::first();

        $data = [
            'buyer_name' => 'NIKE GLOBAL',
            'estimated_pcs' => 24000,
            'target_month' => '2026-12',
            'delivery_date' => '2026-12-25',
            'machine_type_id' => $machineType?->id,
            'needle' => '168N',
            'seam' => 'ROSSO',
            'notes' => 'Testing booking pre-order Nike',
        ];

        $response = $this->post(route('bookings.store'), $data);
        $response->assertRedirect(route('bookings.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('order_bookings', [
            'buyer_name' => 'NIKE GLOBAL',
            'estimated_pcs' => 24000,
            'status' => 'HOLD',
        ]);
    }

    public function test_cancel_order_booking(): void
    {
        $booking = OrderBooking::create([
            'buyer_name' => 'ADIDAS TEST',
            'booking_code' => 'BK-TEST-' . rand(1000, 9999),
            'estimated_pcs' => 10000,
            'sisa_pcs' => 10000,
            'target_month' => '2026-12',
            'status' => 'HOLD',
            'created_at' => now(),
        ]);

        $response = $this->post(route('bookings.cancel', $booking), [
            'reason' => 'Permintaan buyer dibatalkan karena kuota dialihkan',
            'qty_cancel_pcs' => 10000,
        ]);

        $response->assertRedirect(route('bookings.index'));

        $this->assertDatabaseHas('order_bookings', [
            'id' => $booking->id,
            'status' => 'CANCELLED',
        ]);

        $this->assertDatabaseHas('order_booking_cancellations', [
            'order_booking_id' => $booking->id,
            'qty_cancel_pcs' => 10000,
        ]);
    }

    public function test_capacity_targets_and_store(): void
    {
        $plant = Plant::first();

        $response = $this->get(route('capacity.targets'));
        $response->assertOk();
        $response->assertSee('Target Produksi &amp; Ekspor Bulanan', false);

        $targetMonth = '2027-' . rand(10, 12);
        $data = [
            'plant_id' => $plant->id,
            'target_month' => $targetMonth,
            'target_export_pcs' => 850000,
            'target_local_pcs' => 50000,
            'target_efficiency' => 88.5,
        ];

        $postResponse = $this->post(route('capacity.targets.store'), $data);
        $postResponse->assertRedirect(route('capacity.targets'));

        $this->assertDatabaseHas('monthly_targets', [
            'plant_id' => $plant->id,
            'target_month' => $targetMonth,
            'target_export_pcs' => 850000,
        ]);
    }

    public function test_excel_export_production_monitoring(): void
    {
        $response = $this->get(route('reports.export.production'));
        $response->assertOk();
        $this->assertTrue(str_contains(
            $response->headers->get('content-disposition', ''),
            'monitoring-produksi'
        ));
    }

    public function test_excel_export_defects(): void
    {
        $response = $this->get(route('reports.export.defects'));
        $response->assertOk();
        $this->assertTrue(str_contains(
            $response->headers->get('content-disposition', ''),
            'laporan-cacat-bs'
        ));
    }

    public function test_excel_export_downtimes(): void
    {
        $response = $this->get(route('reports.export.downtimes'));
        $response->assertOk();
        $this->assertTrue(str_contains(
            $response->headers->get('content-disposition', ''),
            'laporan-downtime-mesin'
        ));
    }
}
