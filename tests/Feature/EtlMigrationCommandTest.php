<?php

namespace Tests\Feature;

use App\Models\FactoryArea;
use App\Models\Machine;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EtlMigrationCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_etl_command_runs_dry_run_cleanly(): void
    {
        $this->artisan('migrate:legacy-data', ['--dry-run' => true])
            ->expectsOutputToContain('A-ERP: Zero Data Loss ETL Migration Pipeline')
            ->expectsOutputToContain('0 SELISIH (MATCH)')
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
}
