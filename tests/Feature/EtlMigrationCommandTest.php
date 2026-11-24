<?php

namespace Tests\Feature;

use App\Models\Machine;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EtlMigrationCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_etl_command_runs_dry_run_cleanly(): void
    {
        $this->artisan('migrate:legacy-data', ['--dry-run' => true])
            ->expectsOutputToContain('A-ERP: Zero Data Loss Enterprise ETL Migration Pipeline')
            ->expectsOutputToContain('✓ TERSERAP UTUH')
            ->assertSuccessful();
    }

    public function test_migrated_machines_have_valid_relations(): void
    {
        $count = Machine::count();
        $this->assertGreaterThan(0, $count, 'Machines should be present in database');

        $sample = Machine::with(['factoryArea', 'machineType'])->first();
        $this->assertNotNull($sample);
        $this->assertNotNull($sample->factoryArea);
        $this->assertNotNull($sample->machineType);
        $this->assertStringContainsString('-MC', $sample->machine_number);
    }

    public function test_all_supporting_legacy_domains_are_migrated_cleanly(): void
    {
        $this->assertGreaterThan(0, \App\Models\CompanyHoliday::count(), 'Company holidays should be populated');
        $this->assertGreaterThan(0, \App\Models\OrderBooking::count(), 'Order bookings should be populated');
        $this->assertGreaterThan(0, \App\Models\OrderBookingCancellation::count(), 'Order booking cancellations should be populated');
        $this->assertGreaterThan(0, \App\Models\MaintenanceTicketReply::count(), 'Ticket replies should be populated');
        $this->assertGreaterThan(0, \App\Models\FlowTemplate::count(), 'Flow templates should be populated');
        $this->assertGreaterThan(0, \App\Models\FlowStep::count(), 'Flow steps should be populated');
        $this->assertGreaterThan(0, \App\Models\WipMovement::count(), 'WIP movements should be populated');
        $this->assertGreaterThan(0, \App\Models\MachineServiceRecord::count(), 'Machine service records should be populated');
        $this->assertGreaterThan(0, \App\Models\MachineServiceChecklist::count(), 'Machine service checklists should be populated');
        $this->assertGreaterThan(0, \App\Models\MachineWasteLog::count(), 'Machine waste logs should be populated');
        $this->assertGreaterThan(0, \App\Models\MonthlyTarget::count(), 'Monthly targets should be populated');
        $this->assertGreaterThan(0, \App\Models\UserAreaAccess::count(), 'User area access should be populated');
    }
}
