<?php

namespace Tests\Feature;

use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use App\Models\DowntimeReason;
use App\Models\FactoryArea;
use App\Models\Machine;
use App\Models\MachineDowntime;
use App\Models\MaintenanceTicket;
use App\Models\NeedleConsumption;
use App\Models\User;
use Tests\TestCase;

class MaintenanceAndOeeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $user = User::first();
        if ($user) {
            $this->actingAs($user);
        }
    }

    private function getDependencies(): array
    {
        $area = FactoryArea::first();
        $type = \App\Models\MachineType::first();
        $machine = Machine::first() ?? Machine::create([
            'machine_number' => 'MC-AUTO-01',
            'factory_area_id' => $area->id,
            'machine_type_id' => $type?->id ?? 1,
            'status' => 'RUNNING',
        ]);
        $reason = DowntimeReason::first();
        $user = User::first();

        return compact('machine', 'area', 'reason', 'user');
    }

    public function test_downtime_create_and_store(): void
    {
        $deps = $this->getDependencies();

        $response = $this->get(route('downtimes.create'));
        $response->assertOk();
        $response->assertSee('Catat Downtime / Kerusakan Mesin');

        $data = [
            'machine_id' => $deps['machine']->id,
            'downtime_reason_id' => $deps['reason']->id,
            'start_time' => now()->subHours(2)->format('Y-m-d H:i:s'),
            'end_time' => now()->format('Y-m-d H:i:s'),
            'action_taken' => 'Ganti sinker dial dan setel timing',
        ];

        $postResponse = $this->post(route('downtimes.store'), $data);
        $postResponse->assertRedirect(route('downtimes.index'));

        $this->assertDatabaseHas('machine_downtimes', [
            'machine_id' => $deps['machine']->id,
            'downtime_reason_id' => $deps['reason']->id,
        ]);
    }

    public function test_maintenance_ticket_reply(): void
    {
        $deps = $this->getDependencies();

        $ticket = MaintenanceTicket::create([
            'ticket_number' => 'TKT-TEST-' . rand(1000, 9999),
            'machine_id' => $deps['machine']->id,
            'priority' => MaintenancePriority::HIGH,
            'issue_description' => 'Motor servo berisik saat putaran tinggi',
            'reported_by' => $deps['user']->id,
            'status' => MaintenanceStatus::OPEN,
        ]);

        $replyMsg = 'Teknisi meluncur dengan spare part bearing ' . rand(100, 999);
        $response = $this->post(route('maintenance.reply', $ticket), [
            'message' => $replyMsg,
        ]);

        $response->assertRedirect(route('maintenance.index'));

        $this->assertDatabaseHas('maintenance_ticket_replies', [
            'maintenance_ticket_id' => $ticket->id,
            'message' => $replyMsg,
        ]);
    }

    public function test_pm_checklists_page_and_store(): void
    {
        $deps = $this->getDependencies();

        $response = $this->get(route('maintenance.checklists'));
        $response->assertOk();
        $response->assertSee('Preventive Maintenance (PM) Checklists');

        $techName = 'Mekanik PM ' . rand(100, 999);
        $data = [
            'machine_id' => $deps['machine']->id,
            'factory_area_id' => $deps['area']->id,
            'service_date' => now()->format('Y-m-d'),
            'technician_name' => $techName,
            'inspector_name' => 'Agus QC',
            'items' => [
                [
                    'name' => 'Silinder & Cams',
                    'status' => 'OK',
                    'sparepart_name' => null,
                    'sparepart_qty' => 0,
                ],
                [
                    'name' => 'Timing Belt',
                    'status' => 'NOT_OK',
                    'sparepart_name' => 'Belt 140XL',
                    'sparepart_qty' => 1,
                ],
            ],
        ];

        $postResponse = $this->post(route('maintenance.checklists.store'), $data);
        $postResponse->assertRedirect(route('maintenance.checklists'));

        $this->assertDatabaseHas('machine_service_records', [
            'machine_id' => $deps['machine']->id,
            'technician_name' => $techName,
        ]);

        $this->assertDatabaseHas('machine_service_checklists', [
            'item_name' => 'Timing Belt',
            'status' => 'NOT_OK',
            'sparepart_name' => 'Belt 140XL',
        ]);
    }

    public function test_needle_consumption_page_and_store(): void
    {
        $deps = $this->getDependencies();

        $response = $this->get(route('machines.needles'));
        $response->assertOk();
        $response->assertSee('Penggantian Jarum Mesin');

        $needleType = 'VO 71.85 ' . rand(100, 999);
        $data = [
            'machine_id' => $deps['machine']->id,
            'replacement_date' => now()->format('Y-m-d'),
            'shift' => '1',
            'needle_type' => $needleType,
            'broken_count' => 3,
            'replaced_count' => 3,
            'reason' => 'BROKEN_HOOK',
        ];

        $postResponse = $this->post(route('machines.needles.store'), $data);
        $postResponse->assertRedirect(route('machines.needles'));

        $this->assertDatabaseHas('needle_consumptions', [
            'machine_id' => $deps['machine']->id,
            'needle_type' => $needleType,
            'broken_count' => 3,
            'replaced_count' => 3,
        ]);
    }

    public function test_oee_dashboard_computes_metrics(): void
    {
        $deps = $this->getDependencies();

        MachineDowntime::create([
            'machine_id' => $deps['machine']->id,
            'downtime_reason_id' => $deps['reason']->id,
            'reported_by' => $deps['user']->id,
            'start_time' => now()->format('Y-m-d 08:00:00'),
            'end_time' => now()->format('Y-m-d 09:00:00'),
            'duration_minutes' => 60,
            'action_taken' => 'Perbaikan tension',
        ]);

        $response = $this->get(route('machines.oee', [
            'date' => now()->format('Y-m-d'),
            'area_id' => $deps['area']->id,
        ]));

        $response->assertOk();
        $response->assertSee('Overall Equipment Effectiveness (OEE)');
        $response->assertSee('Overall OEE Rate');
        $response->assertSee('Availability (A)');
    }
}
