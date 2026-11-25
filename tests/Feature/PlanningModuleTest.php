<?php

namespace Tests\Feature;

use App\Models\CompanyHoliday;
use App\Models\DailyPlanningLoad;
use App\Models\FactoryArea;
use App\Models\Machine;
use App\Models\MachineAssignment;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\PlanningAllocation;
use App\Models\SampleApproval;
use Tests\TestCase;

class PlanningModuleTest extends TestCase
{
    public function test_planning_index_routes_are_accessible(): void
    {
        // 1. Tab Allocations
        $response = $this->get(route('planning.index'));
        $response->assertStatus(200);
        $response->assertSee('Perencanaan Produksi', false);
        $response->assertSee('Alokasi Planning', false);

        // 2. Tab Daily Loads
        $responseLoads = $this->get(route('planning.index', ['tab' => 'daily_loads']));
        $responseLoads->assertStatus(200);
        $responseLoads->assertSee('Beban Mesin Harian', false);

        // 3. Tab Machine Assignments
        $responseAssign = $this->get(route('planning.index', ['tab' => 'assignments']));
        $responseAssign->assertStatus(200);
        $responseAssign->assertSee('Penugasan Mesin Fisik', false);
    }

    public function test_store_planning_allocation_successfully(): void
    {
        $order = Order::first();
        $area = FactoryArea::first();

        $data = [
            'order_id' => $order->id,
            'factory_area_id' => $area->id,
            'allocated_pcs' => 15000,
            'target_start_date' => now()->toDateString(),
            'target_end_date' => now()->addDays(10)->toDateString(),
        ];

        $response = $this->post(route('planning.allocations.store'), $data);
        $response->assertRedirect(route('planning.index', ['tab' => 'allocations']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('planning_allocations', [
            'order_id' => $order->id,
            'allocated_pcs' => 15000,
        ]);
    }

    public function test_store_daily_load_successfully(): void
    {
        $allocation = PlanningAllocation::first();

        $data = [
            'planning_allocation_id' => $allocation->id,
            'plan_date' => now()->addDays(5)->toDateString(),
            'planned_machine_count' => 8,
            'daily_target_pcs' => 800,
            'start_date' => now()->addDays(5)->toDateString(),
            'stop_date' => now()->addDays(6)->toDateString(),
        ];

        $response = $this->post(route('planning.dailyLoads.store'), $data);
        $response->assertRedirect(route('planning.index', ['tab' => 'daily_loads']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('daily_planning_loads', [
            'planning_allocation_id' => $allocation->id,
            'planned_machine_count' => 8,
            'daily_target_pcs' => 800,
        ]);
    }

    public function test_store_machine_assignment_and_conflict_detection(): void
    {
        $allocation = PlanningAllocation::first();
        $machine = Machine::first();
        $shipment = OrderShipment::first();

        // Cleanup test data from previous runs if any
        MachineAssignment::where('machine_id', $machine->id)
            ->where('start_time', '>=', now()->addYears(2))
            ->delete();

        $startTime = now()->addYears(3)->startOfDay();
        $stopTime = now()->addYears(3)->addDays(5)->endOfDay();

        $data = [
            'planning_allocation_id' => $allocation->id,
            'machine_id' => $machine->id,
            'order_shipment_id' => $shipment->id,
            'start_time' => $startTime->toDateTimeString(),
            'stop_time' => $stopTime->toDateTimeString(),
            'target_pcs' => 3000,
        ];

        // 1. Penugasan awal berhasil
        $response = $this->from(route('planning.index', ['tab' => 'assignments']))
            ->post(route('planning.machineAssignments.store'), $data);
        $response->assertRedirect(route('planning.index', ['tab' => 'assignments']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('machine_assignments', [
            'machine_id' => $machine->id,
            'planning_allocation_id' => $allocation->id,
            'target_pcs' => 3000,
        ]);

        // 2. Penugasan kedua di mesin yang sama pada rentang waktu overlap -> harus ditolak
        $conflictData = [
            'planning_allocation_id' => $allocation->id,
            'machine_id' => $machine->id,
            'order_shipment_id' => $shipment->id,
            'start_time' => $startTime->copy()->addDays(1)->toDateTimeString(),
            'stop_time' => $stopTime->toDateTimeString(),
            'target_pcs' => 2000,
        ];

        $conflictResponse = $this->from(route('planning.index', ['tab' => 'assignments']))
            ->post(route('planning.machineAssignments.store'), $conflictData);

        $conflictResponse->assertRedirect(route('planning.index', ['tab' => 'assignments']));
        $conflictResponse->assertSessionHas('error');

        // Cleanup
        MachineAssignment::where('machine_id', $machine->id)
            ->where('start_time', '>=', now()->addYears(2))
            ->delete();
    }

    public function test_pps_sample_approvals_workflow(): void
    {
        // 1. Akses halaman index PPS
        $response = $this->get(route('planning.pps.index'));
        $response->assertStatus(200);
        $response->assertSee('Gatekeeper Approval Sampel Pre-Production (PPS)');

        $sample = SampleApproval::first();
        $this->assertNotNull($sample);

        // 2. Setujui PPS untuk produksi massal
        $updateData = [
            'is_approved_for_bulk' => 1,
            'sample_stage' => 'PILOT',
            'mechanic_name' => 'Budi Santoso',
            'machine_id' => $sample->machine_id,
            'remarks' => 'Hasil rajutan rapi, siap jalan bulk.',
        ];

        $updateResponse = $this->from(route('planning.pps.index'))
            ->put(route('planning.pps.update', $sample), $updateData);

        $updateResponse->assertRedirect(route('planning.pps.index'));
        $updateResponse->assertSessionHas('success');

        $this->assertDatabaseHas('sample_approvals', [
            'id' => $sample->id,
            'is_approved_for_bulk' => 1,
            'sample_stage' => 'PILOT',
            'mechanic_name' => 'Budi Santoso',
        ]);
    }

    public function test_holidays_crud_workflow(): void
    {
        // 1. Akses halaman kalender libur
        $response = $this->get(route('planning.holidays.index'));
        $response->assertStatus(200);
        $response->assertSee('Master Kalender Hari Libur Pabrik');

        // 2. Tambah hari libur baru
        $holidayDate = date('Y') . '-12-31';
        CompanyHoliday::where('holiday_date', $holidayDate)->delete();

        $postData = [
            'holiday_date' => $holidayDate,
            'description' => 'Malam Tahun Baru PT A-ERP',
            'plant_id' => 1,
        ];

        $storeResponse = $this->from(route('planning.holidays.index'))
            ->post(route('planning.holidays.store'), $postData);

        $storeResponse->assertRedirect(route('planning.holidays.index'));
        $storeResponse->assertSessionHas('success');

        $this->assertDatabaseHas('company_holidays', [
            'holiday_date' => $holidayDate,
            'description' => 'Malam Tahun Baru PT A-ERP',
        ]);

        // 3. Hapus hari libur
        $holiday = CompanyHoliday::where('holiday_date', $holidayDate)->first();
        $deleteResponse = $this->from(route('planning.holidays.index'))
            ->delete(route('planning.holidays.destroy', $holiday));

        $deleteResponse->assertRedirect(route('planning.holidays.index'));
        $deleteResponse->assertSessionHas('success');

        $this->assertDatabaseMissing('company_holidays', [
            'id' => $holiday->id,
        ]);
    }
}
