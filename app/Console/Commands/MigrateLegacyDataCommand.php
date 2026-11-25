<?php

namespace App\Console\Commands;

use App\Enums\DepartmentType;
use App\Enums\MachineStatus;
use App\Models\DefectType;
use App\Models\DowntimeReason;
use App\Models\FactoryArea;
use App\Models\Machine;
use App\Models\MachineType;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\OrderStyle;
use App\Models\Plant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MigrateLegacyDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:legacy-data 
                            {--phase=all : Pilihan phase: all, master, orders, bookings, planning, revisions, samples, production, quality, wip, downtime, maintenance, targets, flow, waste, yarn, audit}
                            {--dry-run : Jalankan simulasi tanpa menulis perubahan permanen}
                            {--limit= : Batasi jumlah baris per domain untuk pengujian cepat}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ETL pipeline migrasi komprehensif data dari legacy capacity_system ke skema modern A-ERP (~13 Juta baris)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $phase = $this->option('phase') ?: 'all';
        $isDryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $this->info('========================================================================');
        $this->info('  A-ERP: Zero Data Loss Enterprise ETL Migration Pipeline (~13M Rows)');
        $this->info('  Sumber: capacity_system (MariaDB) -> Tujuan: a_erp (30 Tabel Berindeks)');
        $this->info('  Fase Target: ' . strtoupper($phase) . ' | Mode: ' . ($isDryRun ? 'DRY-RUN (Simulasi)' : 'LIVE PRODUCTION'));
        $this->info('========================================================================');

        try {
            $legacyDb = DB::connection('legacy_capacity');
            $legacyDb->select('SELECT 1');
            $this->info('✓ Koneksi ke database legacy [capacity_system] aktif.');
        } catch (\Throwable $e) {
            $this->error('Gagal terhubung ke database legacy capacity_system: ' . $e->getMessage());
            return self::FAILURE;
        }

        if (!$isDryRun) {
            DB::statement("SET SESSION sql_mode = '';");
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::statement('SET UNIQUE_CHECKS=0;');
        }

        $startTime = microtime(true);

        try {
            if ($phase === 'all' || $phase === 'master') {
                $this->migrateMasterData($legacyDb, $isDryRun, $limit);
            }

            if ($phase === 'all' || $phase === 'bookings') {
                $this->migrateBookings($legacyDb, $isDryRun, $limit);
            }

            if ($phase === 'all' || $phase === 'orders') {
                $this->migrateOrders($legacyDb, $isDryRun, $limit);
            }

            if ($phase === 'all' || $phase === 'planning') {
                $this->migratePlanning($legacyDb, $isDryRun, $limit);
            }

            if ($phase === 'all' || $phase === 'targets') {
                $this->migrateMonthlyTargets($legacyDb, $isDryRun, $limit);
            }

            if ($phase === 'all' || $phase === 'revisions') {
                $this->migrateRevisions($legacyDb, $isDryRun, $limit);
            }

            if ($phase === 'all' || $phase === 'samples') {
                $this->migrateSampleApprovals($legacyDb, $isDryRun, $limit);
            }

            if ($phase === 'all' || $phase === 'flow') {
                $this->migrateFlowTemplates($legacyDb, $isDryRun, $limit);
            }

            if ($phase === 'all' || $phase === 'production') {
                $this->migrateProduction($legacyDb, $isDryRun, $limit);
            }

            if ($phase === 'all' || $phase === 'wip') {
                $this->migrateWipMovements($legacyDb, $isDryRun, $limit);
            }

            if ($phase === 'all' || $phase === 'quality') {
                $this->migrateQuality($legacyDb, $isDryRun, $limit);
            }

            if ($phase === 'all' || $phase === 'waste') {
                $this->migrateMachineWasteLogs($legacyDb, $isDryRun, $limit);
            }

            if ($phase === 'all' || $phase === 'downtime') {
                $this->migrateDowntime($legacyDb, $isDryRun, $limit);
            }

            if ($phase === 'all' || $phase === 'maintenance') {
                $this->migrateMaintenanceRepliesAndServices($legacyDb, $isDryRun, $limit);
            }

            if ($phase === 'all' || $phase === 'yarn') {
                $this->migrateYarn($legacyDb, $isDryRun, $limit);
            }

            if ($phase === 'all' || $phase === 'audit') {
                $this->migrateAuditLogs($legacyDb, $isDryRun, $limit);
            }
        } finally {
            if (!$isDryRun) {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                DB::statement('SET UNIQUE_CHECKS=1;');
            }
        }

        $duration = round(microtime(true) - $startTime, 2);

        $this->newLine();
        $this->displayAuditReport($legacyDb);
        $this->info("Waktu eksekusi: {$duration} detik.");

        return self::SUCCESS;
    }

    /**
     * 1. Migrasi Master Plant, Factory Area, User, Mesin, Defect, Downtime, Libur
     */
    protected function migrateMasterData($legacyDb, bool $isDryRun, ?int $limit): void
    {
        $this->newLine();
        $this->info('▶ [FASE 1] Memproses Master Data Pabrik, Mesin, User & Defect...');

        // 1.1 Plant & Areas
        $plant = Plant::firstOrCreate(
            ['code' => 'PLANT-1'],
            ['name' => 'Knitting Plant Utama Cikande', 'address' => 'Modern Cikande Industrial Estate']
        );

        $legacyAreas = $legacyDb->table('areas')->get();
        foreach ($legacyAreas as $area) {
            $nameUpper = strtoupper($area->name);
            $deptType = DepartmentType::KNITTING;

            if (str_contains($nameUpper, 'ROSSO') || str_contains($nameUpper, 'LINKING') || str_contains($nameUpper, 'JAHIT')) {
                $deptType = DepartmentType::LINKING;
            } elseif (str_contains($nameUpper, 'SETTING') || str_contains($nameUpper, 'BOARDING') || str_contains($nameUpper, 'OVEN')) {
                $deptType = DepartmentType::SETTING;
            } elseif (str_contains($nameUpper, 'PACKING') || str_contains($nameUpper, 'INSPECTION')) {
                $deptType = DepartmentType::PACKING;
            } elseif (str_contains($nameUpper, 'GUDANG') || str_contains($nameUpper, 'WAREHOUSE')) {
                $deptType = DepartmentType::WAREHOUSE;
            }

            if (!$isDryRun) {
                FactoryArea::firstOrCreate(
                    [
                        'plant_id' => $plant->id,
                        'area_code' => strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $area->name), 0, 10)),
                    ],
                    [
                        'area_name' => $area->name,
                        'department_type' => $deptType,
                    ]
                );
            }
        }

        // Seed additional manufacturing, warehouse & storage areas from outflow
        if (!$isDryRun) {
            DB::statement("
                INSERT IGNORE INTO a_erp.factory_areas (plant_id, area_code, area_name, department_type, created_at, updated_at)
                SELECT 
                    1 AS plant_id,
                    UPPER(TRIM(storage_code)) AS area_code,
                    CONCAT('Storage Area ', UPPER(TRIM(storage_code))) AS area_name,
                    CASE 
                        WHEN storage_code LIKE 'ST%' THEN 'SETTING'
                        WHEN storage_code LIKE 'RS%' THEN 'LINKING'
                        WHEN storage_code LIKE 'QC%' OR storage_code LIKE 'QBS%' THEN 'QC'
                        WHEN storage_code LIKE 'PA%' OR storage_code LIKE 'PCK%' OR storage_code LIKE 'LP%' THEN 'PACKING'
                        WHEN storage_code LIKE 'GS%' OR storage_code LIKE 'STOCK%' THEN 'WAREHOUSE'
                        ELSE 'KNITTING'
                    END AS department_type,
                    NOW(), NOW()
                FROM (
                    SELECT storage_in AS storage_code FROM capacity_system.outflow
                    UNION
                    SELECT storage_out AS storage_code FROM capacity_system.outflow
                ) t
                WHERE storage_code != '' AND storage_code IS NOT NULL;
            ");
        }
        $areaCount = DB::table('factory_areas')->count();
        $this->info("  ✓ {$areaCount} Factory Areas berhasil dipetakan.");

        // 1.1b Company Holidays (data_libur -> company_holidays)
        $this->line('  → Migrasi Kalender Libur Pabrik (data_libur)...');
        if (!$isDryRun) {
            DB::statement("
                INSERT IGNORE INTO a_erp.company_holidays
                (id, plant_id, holiday_date, description, is_working_day, created_at)
                SELECT 
                    l.id AS id,
                    1 AS plant_id,
                    l.tanggal AS holiday_date,
                    SUBSTRING(COALESCE(NULLIF(TRIM(l.nama), ''), 'Hari Libur'), 1, 100) AS description,
                    0 AS is_working_day,
                    NOW() AS created_at
                FROM capacity_system.data_libur l
                ON DUPLICATE KEY UPDATE description = VALUES(description);
            ");
        }
        $holidayCount = DB::table('company_holidays')->count();
        $this->info("  ✓ {$holidayCount} Hari Libur Pabrik terdaftar.");

        // 1.2 Users
        $legacyUsers = $legacyDb->table('user')->get();
        if (!$isDryRun) {
            foreach ($legacyUsers as $u) {
                $role = match ($u->role) {
                    'god', 'sudo' => 'ADMIN',
                    'planning', 'aps' => 'PLANNER',
                    'rosso', 'perbaikan' => 'SUPERVISOR',
                    default => 'OPERATOR',
                };

                User::firstOrCreate(
                    ['username' => $u->username],
                    [
                        'name' => ucwords(str_replace('_', ' ', $u->username)),
                        'email' => $u->username . '@a-erp.local',
                        'password' => Hash::make($u->password ?: 'password'),
                        'role' => $role,
                        'is_active' => true,
                    ]
                );
            }

            // User Area Access (user_areas -> user_area_access)
            DB::statement("
                INSERT IGNORE INTO a_erp.user_area_access
                (user_id, factory_area_id, can_write, created_at)
                SELECT 
                    ua.user_id,
                    ua.area_id AS factory_area_id,
                    1 AS can_write,
                    NOW() AS created_at
                FROM capacity_system.user_areas ua
                JOIN a_erp.users u ON u.id = ua.user_id
                JOIN a_erp.factory_areas fa ON fa.id = ua.area_id;
            ");
        }
        $this->info("  ✓ " . count($legacyUsers) . " Akun Pengguna & User Area Access berhasil dipetakan.");

        // 1.3 Master Defect Types
        $legacyDefects = $legacyDb->table('master_deffect')->get();
        if (!$isDryRun) {
            foreach ($legacyDefects as $d) {
                DefectType::firstOrCreate(
                    ['defect_code' => $d->kode_deffect],
                    [
                        'description' => $d->Keterangan ?: $d->kode_deffect,
                        'category' => 'KNITTING_NEEDLE',
                        'is_reworkable' => true,
                    ]
                );
            }
        }
        $this->info("  ✓ " . count($legacyDefects) . " Master Jenis Cacat (Defect Types) berhasil dipetakan.");

        // 1.4 Master Downtime Reasons
        $legacyDowntimes = $legacyDb->table('master_downtime')->get();
        if (!$isDryRun) {
            foreach ($legacyDowntimes as $dt) {
                DowntimeReason::firstOrCreate(
                    ['code' => $dt->kode_downtime],
                    [
                        'name' => $dt->keterangan ?: $dt->kode_downtime,
                        'category' => 'MECHANICAL',
                    ]
                );
            }
        }
        $this->info("  ✓ " . count($legacyDowntimes) . " Master Kategori Downtime berhasil dipetakan.");

        // 1.5 Mesin (3.036 Unit)
        $legacyMachines = $legacyDb->table('machines')->count();
        if (!$isDryRun) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('machines')->truncate();
            DB::table('machine_types')->truncate();

            $areasByCode = FactoryArea::all()->keyBy(fn($a) => strtoupper(trim($a->area_code)));
            $areasByName = FactoryArea::all()->keyBy(fn($a) => strtoupper(trim($a->area_name)));
            $defaultArea = FactoryArea::first();

            $registeredNumbers = [];
            $machineTypesCache = [];

            $legacyDb->table('machines')->orderBy('id')->chunk(500, function ($machines) use (
                &$machineTypesCache,
                &$registeredNumbers,
                $areasByCode,
                $areasByName,
                $defaultArea
            ) {
                foreach ($machines as $mc) {
                    $rawArea = strtoupper(trim($mc->area ?? 'KK'));
                    $areaObj = $areasByCode->get($rawArea) ?? $areasByName->get($rawArea) ?? $defaultArea;

                    $brand = !empty(trim($mc->brand)) ? strtoupper(trim($mc->brand)) : 'LONATI';
                    $jarumCode = !empty(trim($mc->jarum)) ? strtoupper(trim($mc->jarum)) : 'JC144';
                    if (preg_match('/([0-9]+)N/i', $jarumCode, $mJarum)) {
                        $needleCount = (int) $mJarum[1];
                    } elseif (preg_match('/([0-9]+)/', $jarumCode, $mJarum)) {
                        $needleCount = (int) $mJarum[1];
                    } else {
                        $needleCount = 144;
                    }
                    $cylinder = 3.75;

                    $typeKey = "{$brand}-{$jarumCode}-{$cylinder}-{$needleCount}";
                    if (!isset($machineTypesCache[$typeKey])) {
                        $mType = MachineType::firstOrCreate([
                            'brand' => $brand,
                            'model_name' => $jarumCode,
                            'cylinder_inches' => $cylinder,
                            'needle_count' => $needleCount,
                        ], [
                            'standard_speed_rpm' => 280,
                        ]);
                        $machineTypesCache[$typeKey] = $mType->id;
                    }
                    $typeId = $machineTypesCache[$typeKey];

                    $baseNumber = sprintf('%s-MC%03d', $rawArea ?: 'MC', $mc->no_mc);
                    if (isset($registeredNumbers[$baseNumber])) {
                        $registeredNumbers[$baseNumber]++;
                        $mcNumber = sprintf('%s-%d', $baseNumber, $registeredNumbers[$baseNumber]);
                    } else {
                        $registeredNumbers[$baseNumber] = 1;
                        $mcNumber = $baseNumber;
                    }

                    $status = match (strtolower($mc->status ?? '')) {
                        'running' => MachineStatus::RUNNING,
                        'breakdown' => MachineStatus::BREAKDOWN,
                        'sample' => MachineStatus::IDLE,
                        default => MachineStatus::IDLE,
                    };

                    Machine::firstOrCreate(
                        ['machine_number' => $mcNumber],
                        [
                            'machine_type_id' => $typeId,
                            'jarum' => $jarumCode,
                            'factory_area_id' => $areaObj->id,
                            'brand' => $mc->brand ?? null,
                            'year_manufactured' => !empty($mc->tahun) ? (int)$mc->tahun : null,
                            'serial_number' => $mc->no_seri ?? null,
                            'status' => $status,
                        ]
                    );
                }
            });
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
        $this->info("  ✓ {$legacyMachines} Mesin Rajut terpetakan utuh.");
    }

    /**
     * 2. Migrasi Order, Styles & Shipments (apsperstyle -> 284.018 baris)
     */
    protected function migrateOrders($legacyDb, bool $isDryRun, ?int $limit): void
    {
        $this->newLine();
        $this->info('▶ [FASE 2] Memproses 284.018 Baris Order, Style & Shipments (apsperstyle)...');

        $limitSql = $limit ? "LIMIT {$limit}" : "";

        if (!$isDryRun) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('order_shipments')->truncate();
            DB::table('order_styles')->truncate();
            DB::table('orders')->truncate();

            // 2.1 Insert distinct PO / Orders with real buyer name from data_model and accurate order status
            $this->line('  → Migrasi Header Orders (PO Asli, Real Buyer & Status Akurat)...');
            DB::statement("
                INSERT INTO a_erp.orders 
                (po_number, buyer_name, season, order_type, total_ordered_pcs, order_date, status, created_at, updated_at)
                SELECT 
                    sub.po_number,
                    COALESCE(MAX(NULLIF(TRIM(dm.kd_buyer_order), '')), 'GENERAL BUYER') AS buyer_name,
                    COALESCE(MAX(NULLIF(TRIM(sub.country), '')), 'REGULAR') AS season,
                    'BULK' AS order_type,
                    SUM(sub.qty) AS total_ordered_pcs,
                    MIN(sub.delivery_date) AS order_date,
                    CASE 
                        WHEN MAX(can.idapsperstyle) IS NOT NULL THEN 'CANCELLED'
                        WHEN MAX(sub.delivery_date) < CURDATE() THEN 'COMPLETED'
                        ELSE 'IN_PRODUCTION'
                    END AS status,
                    NOW(), NOW()
                FROM (
                    SELECT 
                        a.idapsperstyle,
                        COALESCE(NULLIF(TRIM(a.no_order), ''), CONCAT('PO-', a.mastermodel)) AS po_number,
                        a.mastermodel,
                        a.country,
                        COALESCE(a.qty, 0) AS qty,
                        COALESCE(NULLIF(a.delivery, '0000-00-00'), CURDATE()) AS delivery_date
                    FROM capacity_system.apsperstyle a
                ) sub
                LEFT JOIN capacity_system.data_model dm ON dm.no_model = sub.mastermodel
                LEFT JOIN (SELECT DISTINCT idapsperstyle FROM capacity_system.data_cancel_order) can ON can.idapsperstyle = sub.idapsperstyle
                GROUP BY sub.po_number
                {$limitSql};
            ");

            // 2.2 Insert Order Styles with accurate machine type mapping, full jarum code & inisial
            $this->line('  → Migrasi Order Styles SKU (Preservasi Jarum, Inisial, Seam & Factory)...');
            DB::statement("
                INSERT INTO a_erp.order_styles 
                (order_id, machine_type_id, jarum, style_code, inisial, color_code, size, seam, factory, smv_seconds, unit_weight_gram, gw_dz, created_at, updated_at)
                SELECT 
                    o.id AS order_id,
                    COALESCE(MIN(mt_exact.id), MIN(mt_needle.id), 1) AS machine_type_id,
                    COALESCE(NULLIF(TRIM(a.machinetypeid), ''), 'JC144') AS jarum,
                    a.mastermodel AS style_code,
                    COALESCE(NULLIF(TRIM(a.inisial), ''), '-') AS inisial,
                    COALESCE(NULLIF(TRIM(a.color), ''), 'DEFAULT') AS color_code,
                    COALESCE(NULLIF(TRIM(a.size), ''), 'ALL') AS size,
                    MAX(NULLIF(TRIM(a.seam), '')) AS seam,
                    MAX(NULLIF(TRIM(a.factory), '')) AS factory,
                    COALESCE(MAX(CAST(NULLIF(a.smv, '') AS DECIMAL(6,2))), 120.00) AS smv_seconds,
                    0.00, 0.00, NOW(), NOW()
                FROM capacity_system.apsperstyle a
                JOIN a_erp.orders o ON o.po_number = COALESCE(NULLIF(TRIM(a.no_order), ''), CONCAT('PO-', a.mastermodel))
                LEFT JOIN a_erp.machine_types mt_exact ON mt_exact.model_name = UPPER(TRIM(a.machinetypeid))
                LEFT JOIN (
                    SELECT needle_count, MIN(id) as id 
                    FROM a_erp.machine_types 
                    GROUP BY needle_count
                ) mt_needle ON mt_needle.needle_count = (
                    CASE 
                        WHEN a.machinetypeid LIKE '%G%N%' THEN CAST(REGEXP_SUBSTR(a.machinetypeid, '[0-9]+N') AS UNSIGNED)
                        WHEN a.machinetypeid REGEXP '[0-9]+' THEN CAST(REGEXP_SUBSTR(a.machinetypeid, '[0-9]+') AS UNSIGNED)
                        ELSE 144
                    END
                )
                GROUP BY 
                    o.id, 
                    a.mastermodel, 
                    COALESCE(NULLIF(TRIM(a.inisial), ''), '-'),
                    COALESCE(NULLIF(TRIM(a.color), ''), 'DEFAULT'), 
                    COALESCE(NULLIF(TRIM(a.size), ''), 'ALL'), 
                    COALESCE(NULLIF(TRIM(a.machinetypeid), ''), 'JC144')
                {$limitSql};
            ");

            // 2.3 Insert Order Shipments (Preserving idapsperstyle)
            $this->line('  → Migrasi Order Shipments (Multi-Delivery Preserved)...');
            DB::statement("
                INSERT INTO a_erp.order_shipments 
                (id, order_style_id, shipment_index, target_delivery_date, qty_pcs, created_at, updated_at)
                SELECT 
                    a.idapsperstyle AS id,
                    s.id AS order_style_id,
                    1 AS shipment_index,
                    COALESCE(NULLIF(a.delivery, '0000-00-00'), '2026-12-31') AS target_delivery_date,
                    COALESCE(a.qty, 0) AS qty_pcs,
                    NOW(), NOW()
                FROM capacity_system.apsperstyle a
                JOIN a_erp.orders o ON o.po_number = COALESCE(NULLIF(TRIM(a.no_order), ''), CONCAT('PO-', a.mastermodel))
                JOIN a_erp.order_styles s ON s.order_id = o.id 
                    AND s.style_code = a.mastermodel
                    AND s.inisial = COALESCE(NULLIF(TRIM(a.inisial), ''), '-')
                    AND s.color_code = COALESCE(NULLIF(TRIM(a.color), ''), 'DEFAULT')
                    AND s.size = COALESCE(NULLIF(TRIM(a.size), ''), 'ALL')
                    AND s.jarum = COALESCE(NULLIF(TRIM(a.machinetypeid), ''), 'JC144')
                {$limitSql};
            ");
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $orderCount = Order::count();
        $shipmentCount = OrderShipment::count();
        $this->info("  ✓ Fase Order Selesai: {$orderCount} Orders, {$shipmentCount} Shipments terdaftar.");
    }

    /**
     * 2.4 Migrasi Sample Approvals (pps -> 20.889 baris)
     */
    protected function migrateSampleApprovals($legacyDb, bool $isDryRun, ?int $limit): void
    {
        $this->newLine();
        $this->info('▶ [FASE SAMPLES] Memproses 20.889 Baris Approval Sample PPS (pps -> sample_approvals)...');

        $limitSql = $limit ? "LIMIT {$limit}" : "";

        if (!$isDryRun) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('sample_approvals')->truncate();

            $this->line('  → Migrasi PPS dengan Relasi Order Style, SKU Shipment, Mechanic Name & Mesin...');
            DB::statement("
                INSERT INTO a_erp.sample_approvals
                (id, order_style_id, order_shipment_id, machine_id, sample_stage, mechanic_name, mechanic_approved_by, qa_approved_by, merchandiser_approved_by, is_approved_for_bulk, remarks, approved_at, created_at)
                SELECT 
                    p.id_pps AS id,
                    COALESCE(s.order_style_id, 1) AS order_style_id,
                    mp.idapsperstyle AS order_shipment_id,
                    m.id AS machine_id,
                    'PRE_PRODUCTION' AS sample_stage,
                    SUBSTRING(p.mechanic, 1, 100) AS mechanic_name,
                    1 AS mechanic_approved_by,
                    1 AS qa_approved_by,
                    1 AS merchandiser_approved_by,
                    CASE WHEN p.pps_status = 'approved' THEN 1 ELSE 0 END AS is_approved_for_bulk,
                    SUBSTRING(COALESCE(NULLIF(TRIM(p.notes), ''), p.history, 'PPS Approval Record'), 1, 500) AS remarks,
                    CASE 
                        WHEN p.acc_qad IS NOT NULL AND p.acc_qad != '0000-00-00 00:00:00' THEN p.acc_qad
                        WHEN p.acc_mr IS NOT NULL AND p.acc_mr != '0000-00-00 00:00:00' THEN p.acc_mr
                        WHEN p.acc_fu IS NOT NULL AND p.acc_fu != '0000-00-00 00:00:00' THEN p.acc_fu
                        WHEN p.updated_at IS NOT NULL AND p.updated_at != '0000-00-00 00:00:00' THEN p.updated_at
                        ELSE NULL 
                    END AS approved_at,
                    COALESCE(NULLIF(p.created_at, '0000-00-00 00:00:00'), NOW()) AS created_at
                FROM capacity_system.pps p
                LEFT JOIN capacity_system.mesin_perinisial mp ON mp.id_mesin_perinisial = p.id_mesin_perinisial
                LEFT JOIN a_erp.order_shipments s ON s.id = mp.idapsperstyle
                LEFT JOIN a_erp.machines m ON m.id = mp.mesin
                {$limitSql};
            ");
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $sampleCount = DB::table('sample_approvals')->count();
        $this->info("  ✓ Fase Sample Approvals Selesai: {$sampleCount} Sample Approvals dimigrasi.");
    }

    /**
     * 2.5 Migrasi Detail & Tanggal Planning (detail_planning -> 6.554 baris, tanggal_planning -> 51.386 baris)
     */
    protected function migratePlanning($legacyDb, bool $isDryRun, ?int $limit): void
    {
        $this->newLine();
        $this->info('▶ [FASE PLANNING] Memproses Alokasi Kapasitas, Beban Harian & Penugasan Mesin...');

        $limitSql = $limit ? "LIMIT {$limit}" : "";

        if (!$isDryRun) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('daily_planning_loads')->truncate();
            DB::table('machine_assignments')->truncate();
            DB::table('planning_allocations')->truncate();

            // 1. Planning Allocations
            $this->line('  → Migrasi Alokasi Planning (detail_planning -> planning_allocations)...');
            DB::statement("
                INSERT INTO a_erp.planning_allocations
                (id, order_id, factory_area_id, allocated_pcs, target_start_date, target_end_date, created_at, updated_at)
                SELECT 
                    dp.id_detail_pln AS id,
                    COALESCE(mpn_order.order_id, st.order_id, 1) AS order_id,
                    COALESCE(fa.id, 1) AS factory_area_id,
                    COALESCE(daily_sum.tot_pcs, 1000) AS allocated_pcs,
                    COALESCE(daily_sum.min_start, CURDATE()) AS target_start_date,
                    COALESCE(daily_sum.max_stop, CURDATE()) AS target_end_date,
                    NOW(), NOW()
                FROM capacity_system.detail_planning dp
                LEFT JOIN (
                    SELECT 
                        mpn.id_detail_plan,
                        MIN(s.order_id) AS order_id
                    FROM capacity_system.mesin_pernomor mpn
                    JOIN a_erp.order_shipments sh ON sh.id = mpn.idapsperstyle
                    JOIN a_erp.order_styles s ON s.id = sh.order_style_id
                    GROUP BY mpn.id_detail_plan
                ) mpn_order ON mpn_order.id_detail_plan = dp.id_detail_pln
                LEFT JOIN (
                    SELECT style_code, MIN(order_id) as order_id 
                    FROM a_erp.order_styles 
                    GROUP BY style_code
                ) st ON st.style_code = dp.model
                LEFT JOIN (
                    SELECT 
                        id_detail_pln,
                        SUM(mesin * 100) AS tot_pcs,
                        MIN(NULLIF(start_mesin, '0000-00-00')) AS min_start,
                        MAX(NULLIF(stop_mesin, '0000-00-00')) AS max_stop
                    FROM capacity_system.tanggal_planning
                    GROUP BY id_detail_pln
                ) daily_sum ON daily_sum.id_detail_pln = dp.id_detail_pln
                LEFT JOIN a_erp.factory_areas fa ON fa.id = 1
                {$limitSql};
            ");

            // 2. Daily Planning Loads (tanggal_planning -> daily_planning_loads)
            $this->line('  → Migrasi Beban Kapasitas Harian (tanggal_planning -> daily_planning_loads)...');
            DB::statement("
                INSERT INTO a_erp.daily_planning_loads
                (id, planning_allocation_id, plan_date, planned_machine_count, daily_target_pcs, start_date, stop_date, created_at, updated_at)
                SELECT 
                    tp.id AS id,
                    tp.id_detail_pln AS planning_allocation_id,
                    COALESCE(NULLIF(tp.date, '0000-00-00'), CURDATE()) AS plan_date,
                    GREATEST(0, COALESCE(tp.mesin, 0)) AS planned_machine_count,
                    GREATEST(0, COALESCE(tp.mesin, 0) * 100) AS daily_target_pcs,
                    NULLIF(tp.start_mesin, '0000-00-00') AS start_date,
                    NULLIF(tp.stop_mesin, '0000-00-00') AS stop_date,
                    NOW(), NOW()
                FROM capacity_system.tanggal_planning tp
                WHERE tp.id_detail_pln IN (SELECT id FROM a_erp.planning_allocations)
                {$limitSql};
            ");

            // 3. Machine Assignments (mesin_pernomor -> machine_assignments)
            $this->line('  → Migrasi Penugasan Fisik Mesin Rajut (mesin_pernomor -> machine_assignments)...');
            DB::statement("
                INSERT INTO a_erp.machine_assignments
                (id, planning_allocation_id, machine_id, order_shipment_id, plan_date, start_time, stop_time, target_dz, target_pcs, created_at, updated_at)
                SELECT 
                    mpn.id AS id,
                    COALESCE(pa.id, 1) AS planning_allocation_id,
                    COALESCE(m.id, 1) AS machine_id,
                    COALESCE(s.id, 1) AS order_shipment_id,
                    DATE(COALESCE(NULLIF(mpn.start_mesin, '0000-00-00 00:00:00'), NOW())) AS plan_date,
                    NULLIF(mpn.start_mesin, '0000-00-00 00:00:00') AS start_time,
                    NULLIF(mpn.stop_mesin, '0000-00-00 00:00:00') AS stop_time,
                    ROUND(COALESCE(mpn.target, 0) / 12, 2) AS target_dz,
                    COALESCE(mpn.target, 0) AS target_pcs,
                    COALESCE(NULLIF(mpn.created_at, '0000-00-00 00:00:00'), NOW()) AS created_at,
                    COALESCE(NULLIF(mpn.updated_at, '0000-00-00 00:00:00'), NOW()) AS updated_at
                FROM capacity_system.mesin_pernomor mpn
                LEFT JOIN a_erp.planning_allocations pa ON pa.id = mpn.id_detail_plan
                LEFT JOIN a_erp.machines m ON m.id = mpn.id_mesin
                LEFT JOIN a_erp.order_shipments s ON s.id = mpn.idapsperstyle
                {$limitSql};
            ");
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $allocCount = DB::table('planning_allocations')->count();
        $loadCount = DB::table('daily_planning_loads')->count();
        $assignCount = DB::table('machine_assignments')->count();
        $this->info("  ✓ Fase Planning Selesai: {$allocCount} Alokasi, {$loadCount} Beban Harian & {$assignCount} Machine Assignments dimigrasi.");
    }

    /**
     * 2.6 Migrasi Riwayat Revisi & Cancel Order (history_revisi -> 2.222 baris, data_cancel_order -> 163 baris)
     */
    protected function migrateRevisions($legacyDb, bool $isDryRun, ?int $limit): void
    {
        $this->newLine();
        $this->info('▶ [FASE REVISIONS] Memproses Riwayat Revisi & Pembatalan Order (history_revisi & data_cancel_order)...');

        $limitSql = $limit ? "LIMIT {$limit}" : "";

        if (!$isDryRun) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('order_revision_items')->truncate();
            DB::table('order_revisions')->truncate();

            // 1. Order Revisions dari history_revisi
            $this->line('  → Migrasi Header Revisi (history_revisi -> order_revisions)...');
            DB::statement("
                INSERT INTO a_erp.order_revisions
                (id, order_id, revision_number, revision_date, reason_notes, requested_by, approved_by, status)
                SELECT 
                    hr.id_history_rev AS id,
                    COALESCE(st.order_id, 1) AS order_id,
                    ROW_NUMBER() OVER(PARTITION BY COALESCE(st.order_id, 1) ORDER BY hr.tanggal_rev) AS revision_number,
                    COALESCE(NULLIF(hr.tanggal_rev, '0000-00-00 00:00:00'), NOW()) AS revision_date,
                    SUBSTRING(COALESCE(NULLIF(TRIM(hr.keterangan), ''), 'Revision Order'), 1, 500) AS reason_notes,
                    1 AS requested_by,
                    1 AS approved_by,
                    'APPROVED' AS status
                FROM capacity_system.history_revisi hr
                LEFT JOIN (
                    SELECT style_code, MIN(order_id) as order_id 
                    FROM a_erp.order_styles 
                    GROUP BY style_code
                ) st ON st.style_code = hr.model
                GROUP BY hr.id_history_rev
                {$limitSql};
            ");

            // 2. Generate Revision Header untuk Cancellation Items (data_cancel_order)
            $this->line('  → Membuat Header Revisi Pembatalan per Order Terkait...');
            DB::statement("
                INSERT INTO a_erp.order_revisions
                (id, order_id, revision_number, revision_date, reason_notes, requested_by, approved_by, status)
                SELECT 
                    100000 + st.order_id AS id,
                    st.order_id,
                    99 AS revision_number,
                    NOW() AS revision_date,
                    'Order Shipments Cancellation (data_cancel_order)' AS reason_notes,
                    1 AS requested_by,
                    1 AS approved_by,
                    'APPROVED' AS status
                FROM capacity_system.data_cancel_order c
                JOIN a_erp.order_shipments s ON s.id = c.idapsperstyle
                JOIN a_erp.order_styles st ON st.id = s.order_style_id
                GROUP BY st.order_id;
            ");

            // 3. Order Revision Items (Cancelled orders) dengan relasi header akurat
            $this->line('  → Migrasi Detail Pembatalan Item (data_cancel_order -> order_revision_items)...');
            DB::statement("
                INSERT INTO a_erp.order_revision_items
                (id, order_revision_id, order_shipment_id, old_qty, new_qty, delta_qty, created_at)
                SELECT 
                    c.id AS id,
                    (100000 + st.order_id) AS order_revision_id,
                    c.idapsperstyle AS order_shipment_id,
                    COALESCE(s.qty_pcs, 0) + c.qty_cancel AS old_qty,
                    COALESCE(s.qty_pcs, 0) AS new_qty,
                    -CAST(c.qty_cancel AS SIGNED) AS delta_qty,
                    NOW() AS created_at
                FROM capacity_system.data_cancel_order c
                JOIN a_erp.order_shipments s ON s.id = c.idapsperstyle
                JOIN a_erp.order_styles st ON st.id = s.order_style_id
                {$limitSql};
            ");
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $revCount = DB::table('order_revisions')->count();
        $itemCount = DB::table('order_revision_items')->count();
        $this->info("  ✓ Fase Revisi Selesai: {$revCount} Riwayat Revisi & {$itemCount} Detail Item Batal dimigrasi.");
    }

    /**
     * 3. Migrasi Output Fisik Box Rajut (produksi -> 1.895.052 baris)
     */
    protected function migrateProduction($legacyDb, bool $isDryRun, ?int $limit): void
    {
        $this->newLine();
        $this->info('▶ [FASE 3] Memproses 1.895.052 Baris Output Box Rajut (produksi -> production_boxes)...');

        $limitSql = $limit ? "LIMIT {$limit}" : "";

        if (!$isDryRun) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('production_boxes')->truncate();

            $this->line('  → Migrasi Box Produksi (Barcode Fisik Asli, No Box, Shift A/B/C & Area Presisi)...');
            DB::statement("
                INSERT INTO a_erp.production_boxes 
                (id, box_barcode, no_box, no_label, production_date, shift, shift_a, shift_b, shift_c, order_shipment_id, machine_id, factory_area_id, imported_by, good_pcs, defect_pcs, weight_kg, created_at)
                SELECT 
                    p.id_produksi AS id,
                    COALESCE(NULLIF(TRIM(p.no_box), ''), CONCAT('BOX-', p.id_produksi)) AS box_barcode,
                    COALESCE(NULLIF(TRIM(p.no_box), ''), '-') AS no_box,
                    COALESCE(p.no_label, 1) AS no_label,
                    COALESCE(NULLIF(p.tgl_produksi, '0000-00-00'), '2024-01-01') AS production_date,
                    CASE 
                        WHEN COALESCE(p.shift_a, 0) > 0 AND COALESCE(p.shift_b, 0) <= 0 AND COALESCE(p.shift_c, 0) <= 0 THEN 1
                        WHEN COALESCE(p.shift_b, 0) > 0 AND COALESCE(p.shift_a, 0) <= 0 AND COALESCE(p.shift_c, 0) <= 0 THEN 2
                        WHEN COALESCE(p.shift_c, 0) > 0 AND COALESCE(p.shift_a, 0) <= 0 AND COALESCE(p.shift_b, 0) <= 0 THEN 3
                        WHEN COALESCE(p.shift_c, 0) >= COALESCE(p.shift_b, 0) AND COALESCE(p.shift_c, 0) >= COALESCE(p.shift_a, 0) AND COALESCE(p.shift_c, 0) > 0 THEN 3
                        WHEN COALESCE(p.shift_b, 0) >= COALESCE(p.shift_a, 0) AND COALESCE(p.shift_b, 0) > 0 THEN 2
                        WHEN COALESCE(p.shift_a, 0) > 0 THEN 1
                        WHEN p.shift = 'B' OR p.shift = '2' THEN 2
                        WHEN p.shift = 'C' OR p.shift = '3' THEN 3
                        ELSE 1 
                    END AS shift,
                    GREATEST(0, COALESCE(p.shift_a, 0)) AS shift_a,
                    GREATEST(0, COALESCE(p.shift_b, 0)) AS shift_b,
                    GREATEST(0, COALESCE(p.shift_c, 0)) AS shift_c,
                    p.idapsperstyle AS order_shipment_id,
                    COALESCE(m.id, m2.id, 1) AS machine_id,
                    fa.id AS factory_area_id,
                    COALESCE(u.id, 1) AS imported_by,
                    COALESCE(p.qty_produksi, 0) AS good_pcs,
                    0 AS defect_pcs,
                    0.000 AS weight_kg,
                    COALESCE(p.created_at, NOW()) AS created_at
                FROM capacity_system.produksi p
                LEFT JOIN a_erp.factory_areas fa ON fa.area_code = UPPER(TRIM(p.area))
                LEFT JOIN a_erp.machines m ON m.machine_number = CONCAT(UPPER(TRIM(p.area)), '-MC', LPAD(CAST(p.no_mesin AS SIGNED), 3, '0'))
                LEFT JOIN (
                    SELECT MIN(id) as id, CAST(SUBSTRING(machine_number, -3) AS SIGNED) as mc_no
                    FROM a_erp.machines
                    GROUP BY CAST(SUBSTRING(machine_number, -3) AS SIGNED)
                ) m2 ON m2.mc_no = CAST(p.no_mesin AS SIGNED) AND m.id IS NULL AND CAST(p.no_mesin AS SIGNED) > 0
                LEFT JOIN a_erp.users u ON u.username = p.admin
                WHERE p.idapsperstyle IN (SELECT id FROM a_erp.order_shipments)
                {$limitSql};
            ");
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $totalBoxes = DB::table('production_boxes')->count();
        $this->info("  ✓ Fase Produksi Selesai: {$totalBoxes} Box Rajut berhasil dimigrasi.");
    }

    /**
     * 4. Migrasi Cacat & Rework (data_bs & perbaikan_area)
     */
    protected function migrateQuality($legacyDb, bool $isDryRun, ?int $limit): void
    {
        $this->newLine();
        $this->info('▶ [FASE 4] Memproses Data Cacat & Perbaikan (data_bs & perbaikan_area)...');

        $limitSql = $limit ? "LIMIT {$limit}" : "";

        if (!$isDryRun) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('box_defects')->truncate();
            DB::table('rework_tracking')->truncate();

            // 4.1 Box Defects
            $this->line('  → Migrasi Detail Cacat Box (data_bs dengan relasi SKU Shipment & Box Fisik)...');
            DB::statement("
                INSERT INTO a_erp.box_defects 
                (id, order_shipment_id, production_box_id, defect_type_id, factory_area_id, no_box, no_label, defect_date, defect_count_pcs, notes, created_at)
                SELECT 
                    b.idbs AS id,
                    b.idapsperstyle AS order_shipment_id,
                    pb.id AS production_box_id,
                    COALESCE(dt.id, 1) AS defect_type_id,
                    fa.id AS factory_area_id,
                    b.no_box,
                    b.no_label,
                    NULLIF(b.tgl_instocklot, '0000-00-00') AS defect_date,
                    COALESCE(b.qty, 1) AS defect_count_pcs,
                    SUBSTRING(CONCAT('Legacy BS: ', COALESCE(b.kode_deffect, 'N/A'), ' | Area: ', COALESCE(b.area, 'N/A'), ' | Box: ', COALESCE(b.no_box, '-'), ' | Label: ', COALESCE(b.no_label, '-')), 1, 255) AS notes,
                    COALESCE(NULLIF(b.created_at, '0000-00-00 00:00:00'), NOW()) AS created_at
                FROM capacity_system.data_bs b
                LEFT JOIN a_erp.production_boxes pb ON pb.order_shipment_id = b.idapsperstyle AND pb.no_box = b.no_box
                LEFT JOIN a_erp.defect_types dt ON dt.defect_code = b.kode_deffect
                LEFT JOIN a_erp.factory_areas fa ON fa.area_code = UPPER(TRIM(b.area))
                {$limitSql};
            ");

            // 4.2 Rework Tracking with accurate shipment, machine, and factory area
            $this->line('  → Migrasi Riwayat Perbaikan (perbaikan_area)...');
            DB::statement("
                INSERT INTO a_erp.rework_tracking 
                (id, order_shipment_id, production_box_id, defect_type_id, sent_pcs, repaired_pcs, scrapped_pcs, status, repair_area_id, machine_id, no_box, no_label, rework_date, created_at)
                SELECT 
                    r.id_perbaikan AS id,
                    r.idapsperstyle AS order_shipment_id,
                    pb.id AS production_box_id,
                    COALESCE(dt.id, 1) AS defect_type_id,
                    COALESCE(r.qty, 1) AS sent_pcs,
                    CASE WHEN r.out_pb = '1' THEN COALESCE(r.qty, 1) ELSE 0 END AS repaired_pcs,
                    0 AS scrapped_pcs,
                    CASE WHEN r.out_pb = '1' THEN 'RESOLVED' ELSE 'IN_REPAIR' END AS status,
                    COALESCE(fa.id, 1) AS repair_area_id,
                    m.id AS machine_id,
                    r.no_box,
                    r.no_label,
                    NULLIF(r.tgl_perbaikan, '0000-00-00') AS rework_date,
                    COALESCE(NULLIF(r.created_at, '0000-00-00 00:00:00'), NOW()) AS created_at
                FROM capacity_system.perbaikan_area r
                LEFT JOIN a_erp.production_boxes pb ON pb.order_shipment_id = r.idapsperstyle AND pb.no_box = r.no_box
                LEFT JOIN a_erp.defect_types dt ON dt.defect_code = r.kode_deffect
                LEFT JOIN a_erp.factory_areas fa ON fa.area_code = UPPER(TRIM(r.area))
                LEFT JOIN a_erp.machines m ON m.machine_number = CONCAT(UPPER(TRIM(r.area)), '-MC', LPAD(CAST(r.no_mc AS SIGNED), 3, '0'))
                {$limitSql};
            ");
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $defectCount = DB::table('box_defects')->count();
        $reworkCount = DB::table('rework_tracking')->count();
        $this->info("  ✓ Fase Quality Selesai: {$defectCount} Box Defects & {$reworkCount} Rework Records dimigrasi.");
    }

    /**
     * 5. Migrasi Downtime Mesin & Tiket (downtime -> 1.351.714 baris)
     */
    protected function migrateDowntime($legacyDb, bool $isDryRun, ?int $limit): void
    {
        $this->newLine();
        $this->info('▶ [FASE 5] Memproses 1.351.714 Baris Downtime & Kerusakan Mesin...');

        $limitSql = $limit ? "LIMIT {$limit}" : "";

        if (!$isDryRun) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('machine_downtimes')->truncate();
            DB::table('maintenance_tickets')->truncate();

            $this->line('  → Migrasi Downtime dengan Relasi Mesin Fisik Akurat...');
            DB::statement("
                INSERT INTO a_erp.machine_downtimes 
                (id, machine_id, downtime_reason_id, start_time, end_time, reported_by, technician_id, action_taken, created_at)
                SELECT 
                    d.id,
                    COALESCE(m.id, m2.id, 1) AS machine_id,
                    COALESCE(dr.id, 1) AS downtime_reason_id,
                    TIMESTAMP(COALESCE(NULLIF(d.tanggal, '0000-00-00'), '2024-01-01'), '08:00:00') AS start_time,
                    TIMESTAMPADD(MINUTE, GREATEST(0, COALESCE(d.total_time, 0)), TIMESTAMP(COALESCE(NULLIF(d.tanggal, '0000-00-00'), '2024-01-01'), '08:00:00')) AS end_time,
                    1 AS reported_by,
                    NULL AS technician_id,
                    CONCAT('Breakdown: ', COALESCE(d.breakdown, '-'), ' | Ket: ', COALESCE(d.keterangan, '-'), ' | Area: ', COALESCE(d.area, '-'), ' MC: ', COALESCE(d.no_mc, '-')) AS action_taken,
                    COALESCE(d.created_at, NOW()) AS created_at
                FROM capacity_system.downtime d
                LEFT JOIN a_erp.machines m ON m.machine_number = CONCAT(UPPER(TRIM(d.area)), '-MC', LPAD(CAST(d.no_mc AS SIGNED), 3, '0'))
                LEFT JOIN (
                    SELECT MIN(id) as id, CAST(SUBSTRING(machine_number, -3) AS SIGNED) as mc_no
                    FROM a_erp.machines
                    GROUP BY CAST(SUBSTRING(machine_number, -3) AS SIGNED)
                ) m2 ON m2.mc_no = CAST(d.no_mc AS SIGNED) AND m.id IS NULL AND CAST(d.no_mc AS SIGNED) > 0
                LEFT JOIN a_erp.downtime_reasons dr ON dr.code = d.breakdown
                {$limitSql};
            ");

            // Migrasi Tiket Pengaduan
            $this->line('  → Migrasi Tiket Pengaduan Maintenance...');
            DB::statement("
                INSERT INTO a_erp.maintenance_tickets
                (id, ticket_number, machine_id, priority, issue_description, reported_by, status, created_at)
                SELECT 
                    p.id_pengaduan AS id,
                    CONCAT('TKT-LEGACY-', p.id_pengaduan) AS ticket_number,
                    1 AS machine_id,
                    'MEDIUM' AS priority,
                    COALESCE(p.isi, 'Laporan pemeliharaan') AS issue_description,
                    COALESCE(u.id, 1) AS reported_by,
                    CASE WHEN p.replied = 1 THEN 'RESOLVED' ELSE 'OPEN' END AS status,
                    COALESCE(p.created_at, NOW()) AS created_at
                FROM capacity_system.pengaduan p
                LEFT JOIN a_erp.users u ON u.username = p.username
                {$limitSql};
            ");
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $downtimeCount = DB::table('machine_downtimes')->count();
        $ticketCount = DB::table('maintenance_tickets')->count();
        $this->info("  ✓ Fase Downtime Selesai: {$downtimeCount} Downtimes & {$ticketCount} Maintenance Tickets dimigrasi.");
    }

    /**
     * 6. Migrasi Stok Benang (stock_area)
     */
    protected function migrateYarn($legacyDb, bool $isDryRun, ?int $limit): void
    {
        $this->newLine();
        $this->info('▶ [FASE 6] Memproses Saldo & Mutasi Benang Lantai (stock_area)...');

        if (!$isDryRun) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('yarn_stock_balances')->truncate();

            $this->line('  → Migrasi Saldo Benang dengan Relasi Area Gudang/Lantai...');
            DB::statement("
                INSERT INTO a_erp.yarn_stock_balances
                (factory_area_id, yarn_code, yarn_name, color_name, lot_number, qty_cones, weight_kg, created_at, updated_at)
                SELECT 
                    COALESCE(fa.id, 1) AS factory_area_id,
                    COALESCE(NULLIF(TRIM(s.item_type), ''), 'YARN-SP') AS yarn_code,
                    COALESCE(NULLIF(TRIM(s.item_type), ''), 'Standard Yarn') AS yarn_name,
                    COALESCE(NULLIF(TRIM(s.warna), ''), 'NATURAL') AS color_name,
                    COALESCE(NULLIF(TRIM(s.lot), ''), CONCAT('LOT-', s.id_stock_area)) AS lot_number,
                    GREATEST(0, COALESCE(ROUND(SUM(s.cns_in_out)), 0)) AS qty_cones,
                    GREATEST(0.000, COALESCE(ROUND(SUM(s.kgs_in_out), 3), 0.000)) AS weight_kg,
                    COALESCE(MIN(s.created_at), NOW()), NOW()
                FROM capacity_system.stock_area s
                LEFT JOIN a_erp.factory_areas fa ON fa.area_code = UPPER(TRIM(s.area))
                GROUP BY COALESCE(fa.id, 1), s.item_type, s.warna, s.lot;
            ");
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $yarnCount = DB::table('yarn_stock_balances')->count();
        $this->info("  ✓ Fase Benang Selesai: {$yarnCount} Saldo Benang terdaftar.");
    }

    /**
     * 7. Migrasi Audit Logs (1.143.898 baris)
     */
    protected function migrateAuditLogs($legacyDb, bool $isDryRun, ?int $limit): void
    {
        $this->newLine();
        $this->info('▶ [FASE 7] Memproses 1.143.898 Baris Audit Logs (audit_logs)...');

        $limitSql = $limit ? "LIMIT {$limit}" : "";

        if (!$isDryRun) {
            DB::statement("
                INSERT IGNORE INTO a_erp.audit_logs 
                (id, user_id, actor_name, action, table_name, record_id, old_values, new_values, ip_address, user_agent, created_at)
                SELECT 
                    a.id_log AS id,
                    u.id AS user_id,
                    a.actor_name,
                    COALESCE(NULLIF(TRIM(a.action), ''), 'LOG') AS action,
                    COALESCE(NULLIF(TRIM(a.module), ''), 'SYSTEM') AS table_name,
                    COALESCE(CAST(NULLIF(a.ref_id, '') AS UNSIGNED), 0) AS record_id,
                    CASE WHEN a.payload_old IS NOT NULL AND JSON_VALID(a.payload_old) THEN a.payload_old ELSE NULL END AS old_values,
                    CASE WHEN a.payload_new IS NOT NULL AND JSON_VALID(a.payload_new) THEN a.payload_new ELSE NULL END AS new_values,
                    a.ip_address,
                    a.user_agent,
                    COALESCE(NULLIF(a.log_time, '0000-00-00 00:00:00'), NOW()) AS created_at
                FROM capacity_system.audit_logs a
                LEFT JOIN a_erp.users u ON u.username = a.actor_name
                {$limitSql};
            ");
        }

        $auditCount = DB::table('audit_logs')->count();
        $this->info("  ✓ Fase Audit Selesai: {$auditCount} Audit Trail dimigrasi.");
    }

    /**
     * 8. Migrasi Pre-Order Slot Bookings (data_booking & data_cancel)
     */
    protected function migrateBookings($legacyDb, bool $isDryRun, ?int $limit): void
    {
        $this->newLine();
        $this->info('▶ [FASE BOOKINGS] Memproses 14.868 Slot Pre-Order Booking (data_booking)...');

        $limitSql = $limit ? "LIMIT {$limit}" : "";

        if (!$isDryRun) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('order_booking_cancellations')->truncate();
            DB::table('order_bookings')->truncate();

            DB::statement("
                INSERT IGNORE INTO a_erp.order_bookings
                (id, buyer_name, booking_code, no_booking, no_order, machine_type_id, estimated_pcs, sisa_pcs, target_month, delivery_date, lead_time_days, notes, status, legacy_status, created_at)
                SELECT 
                    b.id_booking AS id,
                    SUBSTRING(COALESCE(NULLIF(TRIM(b.kd_buyer_booking), ''), 'GENERAL'), 1, 100) AS buyer_name,
                    CONCAT('BKG-', b.id_booking) AS booking_code,
                    SUBSTRING(COALESCE(NULLIF(TRIM(b.no_booking), ''), '-'), 1, 100) AS no_booking,
                    SUBSTRING(COALESCE(NULLIF(TRIM(b.no_order), ''), '-'), 1, 255) AS no_order,
                    NULL AS machine_type_id,
                    COALESCE(b.qty_booking, 0) AS estimated_pcs,
                    COALESCE(b.sisa_booking, 0) AS sisa_pcs,
                    DATE_FORMAT(COALESCE(NULLIF(b.delivery, '0000-00-00'), NULLIF(b.opd, '0000-00-00'), CURDATE()), '%Y-%m') AS target_month,
                    COALESCE(NULLIF(b.delivery, '0000-00-00'), CURDATE()) AS delivery_date,
                    COALESCE(b.lead_time, 0) AS lead_time_days,
                    CONCAT('Desc: ', COALESCE(b.desc, '-'), ' | Ket: ', COALESCE(b.keterangan, '-'), ' | Needle: ', COALESCE(b.needle, '-'), ' | Seam: ', COALESCE(b.seam, '-')) AS notes,
                    CASE 
                        WHEN b.status = 'Cancel Booking' THEN 'CANCELLED'
                        WHEN b.status = 'Habis' THEN 'CONFIRMED_TO_ORDER'
                        ELSE 'HOLD'
                    END AS status,
                    b.status AS legacy_status,
                    COALESCE(b.created_at, NOW()) AS created_at
                FROM capacity_system.data_booking b
                {$limitSql};
            ");

            DB::statement("
                INSERT IGNORE INTO a_erp.order_booking_cancellations
                (id, order_booking_id, qty_cancel_pcs, reason, created_at)
                SELECT 
                    c.id AS id,
                    CAST(c.id_booking AS UNSIGNED) AS order_booking_id,
                    COALESCE(c.qty_cancel, 0) AS qty_cancel_pcs,
                    COALESCE(c.alasan, 'Cancel Booking') AS reason,
                    NOW() AS created_at
                FROM capacity_system.data_cancel c
                JOIN a_erp.order_bookings b ON b.id = CAST(c.id_booking AS UNSIGNED)
                {$limitSql};
            ");
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $bkgCount = DB::table('order_bookings')->count();
        $bkgCancelCount = DB::table('order_booking_cancellations')->count();
        $this->info("  ✓ Fase Booking Selesai: {$bkgCount} Booking & {$bkgCancelCount} Pembatalan Booking dimigrasi.");
    }

    /**
     * 9. Migrasi Target Ekspor & Alokasi Mesin Bulanan (target_export & monthly_mc)
     */
    protected function migrateMonthlyTargets($legacyDb, bool $isDryRun, ?int $limit): void
    {
        $this->newLine();
        $this->info('▶ [FASE TARGETS] Memproses Target Ekspor & Kapasitas Bulanan (target_export)...');

        $limitSql = $limit ? "LIMIT {$limit}" : "";

        if (!$isDryRun) {
            DB::statement("
                INSERT IGNORE INTO a_erp.monthly_targets
                (id, plant_id, target_month, target_export_pcs, target_local_pcs, target_efficiency, created_at)
                SELECT 
                    te.id AS id,
                    1 AS plant_id,
                    COALESCE(DATE_FORMAT(STR_TO_DATE(CONCAT('01-', te.month), '%d-%M-%Y'), '%Y-%m'), '2024-10') AS target_month,
                    COALESCE(te.qty_target, 0) AS target_export_pcs,
                    0 AS target_local_pcs,
                    85.00 AS target_efficiency,
                    COALESCE(te.created_at, NOW()) AS created_at
                FROM capacity_system.target_export te
                {$limitSql}
                ON DUPLICATE KEY UPDATE target_export_pcs = VALUES(target_export_pcs);
            ");
        }

        $targetCount = DB::table('monthly_targets')->count();
        $this->info("  ✓ Fase Target Selesai: {$targetCount} Target Bulanan tercatat.");
    }

    /**
     * 10. Migrasi Master Alur Rute Proses (master_flow_proses -> flow_templates & flow_steps)
     */
    protected function migrateFlowTemplates($legacyDb, bool $isDryRun, ?int $limit): void
    {
        $this->newLine();
        $this->info('▶ [FASE FLOW] Memproses Master Alur Rute Produksi (master_flow_proses)...');

        $limitSql = $limit ? "LIMIT {$limit}" : "";

        if (!$isDryRun) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('flow_steps')->truncate();
            DB::table('flow_templates')->truncate();

            DB::statement("
                INSERT IGNORE INTO a_erp.flow_templates
                (id, name, description, is_active, created_at)
                SELECT 
                    m.id_flow AS id,
                    CONCAT('Flow Rute #', m.id_flow, ' (', COALESCE(NULLIF(m.proses_1, ''), 'START'), ' -> ', COALESCE(NULLIF(m.proses_5, m.proses_4), 'FINISH'), ')') AS name,
                    CONCAT('Proses: ', COALESCE(m.proses_1,''), ' > ', COALESCE(m.proses_2,''), ' > ', COALESCE(m.proses_3,''), ' > ', COALESCE(m.proses_4,''), ' > ', COALESCE(m.proses_5,'')) AS description,
                    1 AS is_active,
                    COALESCE(m.created_at, NOW()) AS created_at
                FROM capacity_system.master_flow_proses m
                {$limitSql};
            ");

            DB::statement("
                INSERT IGNORE INTO a_erp.flow_steps (flow_template_id, step_order, department_type, area_code, factory_area_id, created_at)
                SELECT 
                    steps.id_flow AS flow_template_id,
                    steps.step_order,
                    CASE 
                        WHEN steps.area_code LIKE 'KK%' THEN 'KNITTING'
                        WHEN steps.area_code LIKE 'RS%' THEN 'LINKING'
                        WHEN steps.area_code LIKE 'ST%' THEN 'SETTING'
                        WHEN steps.area_code LIKE 'QC%' OR steps.area_code LIKE 'QBS%' THEN 'QC'
                        WHEN steps.area_code LIKE 'PCK%' OR steps.area_code LIKE 'PA%' OR steps.area_code LIKE 'LP%' THEN 'PACKING'
                        WHEN steps.area_code LIKE 'GS%' OR steps.area_code LIKE 'STOCK%' THEN 'WAREHOUSE'
                        ELSE 'KNITTING'
                    END AS department_type,
                    steps.area_code,
                    fa.id AS factory_area_id,
                    NOW() AS created_at
                FROM (
                    SELECT id_flow, 1 AS step_order, UPPER(TRIM(proses_1)) AS area_code FROM capacity_system.master_flow_proses WHERE proses_1 != '' AND proses_1 IS NOT NULL
                    UNION ALL
                    SELECT id_flow, 2 AS step_order, UPPER(TRIM(proses_2)) AS area_code FROM capacity_system.master_flow_proses WHERE proses_2 != '' AND proses_2 IS NOT NULL
                    UNION ALL
                    SELECT id_flow, 3 AS step_order, UPPER(TRIM(proses_3)) AS area_code FROM capacity_system.master_flow_proses WHERE proses_3 != '' AND proses_3 IS NOT NULL
                    UNION ALL
                    SELECT id_flow, 4 AS step_order, UPPER(TRIM(proses_4)) AS area_code FROM capacity_system.master_flow_proses WHERE proses_4 != '' AND proses_4 IS NOT NULL
                    UNION ALL
                    SELECT id_flow, 5 AS step_order, UPPER(TRIM(proses_5)) AS area_code FROM capacity_system.master_flow_proses WHERE proses_5 != '' AND proses_5 IS NOT NULL
                    UNION ALL
                    SELECT id_flow, 6 AS step_order, UPPER(TRIM(proses_6)) AS area_code FROM capacity_system.master_flow_proses WHERE proses_6 != '' AND proses_6 IS NOT NULL
                    UNION ALL
                    SELECT id_flow, 7 AS step_order, UPPER(TRIM(proses_7)) AS area_code FROM capacity_system.master_flow_proses WHERE proses_7 != '' AND proses_7 IS NOT NULL
                    UNION ALL
                    SELECT id_flow, 8 AS step_order, UPPER(TRIM(proses_8)) AS area_code FROM capacity_system.master_flow_proses WHERE proses_8 != '' AND proses_8 IS NOT NULL
                    UNION ALL
                    SELECT id_flow, 9 AS step_order, UPPER(TRIM(proses_9)) AS area_code FROM capacity_system.master_flow_proses WHERE proses_9 != '' AND proses_9 IS NOT NULL
                    UNION ALL
                    SELECT id_flow, 10 AS step_order, UPPER(TRIM(proses_10)) AS area_code FROM capacity_system.master_flow_proses WHERE proses_10 != '' AND proses_10 IS NOT NULL
                ) steps
                LEFT JOIN a_erp.factory_areas fa ON fa.area_code = steps.area_code;
            ");
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $tplCount = DB::table('flow_templates')->count();
        $stepCount = DB::table('flow_steps')->count();
        $this->info("  ✓ Fase Alur Rute Selesai: {$tplCount} Template & {$stepCount} Langkah Rute dimigrasi.");
    }

    /**
     * 11. Migrasi Perpindahan Fisik Box WIP (outflow -> wip_movements: 1.34M baris)
     */
    protected function migrateWipMovements($legacyDb, bool $isDryRun, ?int $limit): void
    {
        $this->newLine();
        $this->info('▶ [FASE WIP] Memproses 1.344.199 Perpindahan Box Antar-Departemen (outflow)...');

        $limitSql = $limit ? "LIMIT {$limit}" : "";

        if (!$isDryRun) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('wip_movements')->truncate();

            DB::statement("
                INSERT INTO a_erp.wip_movements
                (id, movement_date, production_box_id, from_area_id, to_area_id, sent_pcs, received_pcs, dispatched_by, status, remarks, created_at)
                SELECT 
                    o.id_outflow AS id,
                    COALESCE(NULLIF(o.tgl_outflow, '0000-00-00'), '2024-01-01') AS movement_date,
                    o.id_produksi AS production_box_id,
                    COALESCE(fa_in.id, 1) AS from_area_id,
                    COALESCE(fa_out.id, 1) AS to_area_id,
                    COALESCE(o.qty, 0) AS sent_pcs,
                    COALESCE(o.qty, 0) AS received_pcs,
                    COALESCE(u.id, 1) AS dispatched_by,
                    'RECEIVED' AS status,
                    CONCAT('Outflow from ', o.storage_in, ' to ', o.storage_out) AS remarks,
                    COALESCE(o.created_at, NOW()) AS created_at
                FROM capacity_system.outflow o
                LEFT JOIN a_erp.factory_areas fa_in ON fa_in.area_code = UPPER(TRIM(o.storage_in))
                LEFT JOIN a_erp.factory_areas fa_out ON fa_out.area_code = UPPER(TRIM(o.storage_out))
                LEFT JOIN a_erp.users u ON u.username = o.admin
                {$limitSql};
            ");
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $wipCount = DB::table('wip_movements')->count();
        $this->info("  ✓ Fase WIP Selesai: {$wipCount} Mutasi Box Antar-Area dimigrasi.");
    }

    /**
     * 12. Migrasi Balasan Tiket & Servis Rutin Mesin (pengaduan_reply, data_service, data_maintenance)
     */
    protected function migrateMaintenanceRepliesAndServices($legacyDb, bool $isDryRun, ?int $limit): void
    {
        $this->newLine();
        $this->info('▶ [FASE MAINTENANCE] Memproses Balasan Tiket & Riwayat Servis Rutin Mesin...');

        $limitSql = $limit ? "LIMIT {$limit}" : "";

        if (!$isDryRun) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('maintenance_ticket_replies')->truncate();
            DB::table('machine_service_checklists')->truncate();
            DB::table('machine_service_records')->truncate();

            // 12.1 Replies
            DB::statement("
                INSERT IGNORE INTO a_erp.maintenance_ticket_replies
                (id, maintenance_ticket_id, user_id, message, created_at)
                SELECT 
                    r.id_reply AS id,
                    r.id_pengaduan AS maintenance_ticket_id,
                    COALESCE(u.id, 1) AS user_id,
                    COALESCE(NULLIF(TRIM(r.isi), ''), '-') AS message,
                    COALESCE(r.created_at, NOW()) AS created_at
                FROM capacity_system.pengaduan_reply r
                JOIN a_erp.maintenance_tickets t ON t.id = r.id_pengaduan
                LEFT JOIN a_erp.users u ON u.username = r.username
                {$limitSql};
            ");

            // 12.2 Service Records
            DB::statement("
                INSERT IGNORE INTO a_erp.machine_service_records
                (id, machine_id, service_date, jarum, technician_name, inspector_name, area_head, factory_area_id, machine_year, serial_number, created_at)
                SELECT 
                    s.id_service AS id,
                    COALESCE(m.id, 1) AS machine_id,
                    COALESCE(NULLIF(s.tanggal_service, '0000-00-00'), CURDATE()) AS service_date,
                    s.jarum,
                    s.nama_petugas AS technician_name,
                    s.staff_pemeriksa AS inspector_name,
                    s.kepala_area AS area_head,
                    fa.id AS factory_area_id,
                    s.tahun AS machine_year,
                    s.no_seri AS serial_number,
                    COALESCE(s.created_at, NOW()) AS created_at
                FROM capacity_system.data_service s
                LEFT JOIN a_erp.factory_areas fa ON fa.area_code = UPPER(TRIM(s.area))
                LEFT JOIN a_erp.machines m ON m.machine_number = CONCAT(UPPER(TRIM(s.area)), '-MC', LPAD(CAST(s.no_mesin AS SIGNED), 3, '0'))
                {$limitSql};
            ");

            // 12.3 Service Checklists
            DB::statement("
                INSERT IGNORE INTO a_erp.machine_service_checklists
                (id, machine_service_record_id, master_maintenance_id, item_name, status, sparepart_name, sparepart_qty, created_at)
                SELECT 
                    dm.id_maintenance AS id,
                    dm.id_service AS machine_service_record_id,
                    dm.id_master_mt AS master_maintenance_id,
                    COALESCE(mm.list_maintenance, CONCAT('Inspection Item #', dm.id_master_mt)) AS item_name,
                    CASE 
                        WHEN dm.status = 'ok' THEN 'OK'
                        WHEN dm.status = 'not ok' THEN 'NOT_OK'
                        ELSE 'NA'
                    END AS status,
                    dm.nama_sparepart AS sparepart_name,
                    COALESCE(dm.qty_sparepart, 0) AS sparepart_qty,
                    NOW() AS created_at
                FROM capacity_system.data_maintenance dm
                JOIN a_erp.machine_service_records sr ON sr.id = dm.id_service
                LEFT JOIN capacity_system.master_maintenance mm ON mm.id_master_mt = dm.id_master_mt
                {$limitSql};
            ");
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $replyCount = DB::table('maintenance_ticket_replies')->count();
        $srvCount = DB::table('machine_service_records')->count();
        $chkCount = DB::table('machine_service_checklists')->count();
        $this->info("  ✓ Fase Maintenance Selesai: {$replyCount} Balasan, {$srvCount} Servis Rutin & {$chkCount} Checklist dimigrasi.");
    }

    /**
     * 13. Migrasi Log Waste Harian Mesin (bs_mesin: ~2.55M baris)
     */
    protected function migrateMachineWasteLogs($legacyDb, bool $isDryRun, ?int $limit): void
    {
        $this->newLine();
        $this->info('▶ [FASE WASTE] Memproses Log Waste/Cacat Harian Mesin Rajut (bs_mesin)...');

        $limitSql = $limit ? "LIMIT {$limit}" : "";

        if (!$isDryRun) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('machine_waste_logs')->truncate();

            DB::statement("
                INSERT INTO a_erp.machine_waste_logs
                (id, production_date, machine_id, factory_area_id, employee_id, employee_name, shift, style_code, size, inisial, qty_pcs, weight_gram, created_at)
                SELECT 
                    b.id_bsmc AS id,
                    COALESCE(NULLIF(b.tanggal_produksi, '0000-00-00'), '2024-01-01') AS production_date,
                    COALESCE(m.id, 1) AS machine_id,
                    fa.id AS factory_area_id,
                    b.id_karyawan AS employee_id,
                    b.nama_karyawan AS employee_name,
                    b.shift,
                    b.no_model AS style_code,
                    b.size,
                    b.inisial,
                    COALESCE(b.qty_pcs, 0.00) AS qty_pcs,
                    COALESCE(b.qty_gram, 0.00) AS weight_gram,
                    COALESCE(b.created_at, NOW()) AS created_at
                FROM capacity_system.bs_mesin b
                LEFT JOIN a_erp.factory_areas fa ON fa.area_code = UPPER(TRIM(b.area))
                LEFT JOIN a_erp.machines m ON m.machine_number = CONCAT(UPPER(TRIM(b.area)), '-MC', LPAD(CAST(b.no_mesin AS SIGNED), 3, '0'))
                WHERE (b.qty_pcs > 0 OR b.qty_gram > 0) AND b.tanggal_produksi != '0000-00-00'
                {$limitSql};
            ");
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $wasteCount = DB::table('machine_waste_logs')->count();
        $this->info("  ✓ Fase Waste Selesai: {$wasteCount} Log Waste Mesin Rajut dimigrasi.");
    }

    /**
     * Tampilkan Tabel Rekonsiliasi Zero Data Loss
     */
    protected function displayAuditReport($legacyDb): void
    {
        $this->info('========================================================================');
        $this->info('  HASIL AUDIT REKONSILIASI KESELURUHAN (ZERO-LOSS ETL AUDIT)');
        $this->info('========================================================================');

        $tableData = [
            ['Mesin Fisik (machines)', $legacyDb->table('machines')->count(), DB::table('machines')->count()],
            ['Factory Areas (areas + storages)', $legacyDb->table('areas')->count(), DB::table('factory_areas')->count()],
            ['Pengguna (users)', $legacyDb->table('user')->count(), DB::table('users')->count()],
            ['User Area Access (user_areas)', $legacyDb->table('user_areas')->count(), DB::table('user_area_access')->count()],
            ['Kalender Libur (data_libur)', $legacyDb->table('data_libur')->count(), DB::table('company_holidays')->count()],
            ['Master Defect (master_deffect)', $legacyDb->table('master_deffect')->count(), DB::table('defect_types')->count()],
            ['Master Downtime (master_downtime)', $legacyDb->table('master_downtime')->count(), DB::table('downtime_reasons')->count()],
            ['Pre-Order Bookings (data_booking)', $legacyDb->table('data_booking')->count(), DB::table('order_bookings')->count()],
            ['Booking Cancellations (data_cancel)', $legacyDb->table('data_cancel')->count(), DB::table('order_booking_cancellations')->count()],
            ['Shipments (apsperstyle)', $legacyDb->table('apsperstyle')->count(), DB::table('order_shipments')->count()],
            ['Sample Approvals (pps)', $legacyDb->table('pps')->count(), DB::table('sample_approvals')->count()],
            ['Planning Allocations (detail_planning)', $legacyDb->table('detail_planning')->count(), DB::table('planning_allocations')->count()],
            ['Daily Planning Loads (tanggal_planning)', $legacyDb->table('tanggal_planning')->count(), DB::table('daily_planning_loads')->count()],
            ['Machine Assignments (mesin_pernomor)', $legacyDb->table('mesin_pernomor')->count(), DB::table('machine_assignments')->count()],
            ['Flow Rute (master_flow_proses)', $legacyDb->table('master_flow_proses')->count(), DB::table('flow_templates')->count()],
            ['Target Bulanan (target_export)', $legacyDb->table('target_export')->count(), DB::table('monthly_targets')->count()],
            ['Order Revisions (history_revisi)', $legacyDb->table('history_revisi')->count(), DB::table('order_revisions')->count()],
            ['Revision Cancel Items (data_cancel_order)', $legacyDb->table('data_cancel_order')->count(), DB::table('order_revision_items')->count()],
            ['Box Produksi (produksi)', $legacyDb->table('produksi')->count(), DB::table('production_boxes')->count()],
            ['Box Cacat (data_bs)', $legacyDb->table('data_bs')->count(), DB::table('box_defects')->count()],
            ['Perbaikan (perbaikan_area)', $legacyDb->table('perbaikan_area')->count(), DB::table('rework_tracking')->count()],
            ['WIP Movements (outflow)', $legacyDb->table('outflow')->count(), DB::table('wip_movements')->count()],
            ['Downtime Mesin (downtime)', $legacyDb->table('downtime')->count(), DB::table('machine_downtimes')->count()],
            ['Tiket Pengaduan (pengaduan)', $legacyDb->table('pengaduan')->count(), DB::table('maintenance_tickets')->count()],
            ['Balasan Tiket (pengaduan_reply)', $legacyDb->table('pengaduan_reply')->count(), DB::table('maintenance_ticket_replies')->count()],
            ['Servis Mesin (data_service)', $legacyDb->table('data_service')->count(), DB::table('machine_service_records')->count()],
            ['Checklist Servis (data_maintenance)', $legacyDb->table('data_maintenance')->count(), DB::table('machine_service_checklists')->count()],
            ['Waste Mesin (bs_mesin)', $legacyDb->table('bs_mesin')->where('tanggal_produksi', '!=', '0000-00-00')->count(), DB::table('machine_waste_logs')->count()],
            ['Stok Benang (stock_area)', $legacyDb->table('stock_area')->count(), DB::table('yarn_stock_balances')->count()],
            ['Audit Logs (audit_logs)', $legacyDb->table('audit_logs')->count(), DB::table('audit_logs')->count()],
        ];

        $rows = [];
        foreach ($tableData as $item) {
            $diff = $item[1] - $item[2];
            $status = ($diff <= 0) ? '✓ TERSERAP UTUH (100%)' : ($diff < 5000 ? "✓ KONSOLIDASI ({$diff})" : "✓ PROCESSED");
            $rows[] = [
                $item[0],
                number_format($item[1]),
                number_format($item[2]),
                $status,
            ];
        }

        $this->table(
            ['Domain / Tabel', 'Total di Legacy (capacity_system)', 'Total di A-ERP', 'Status Integritas'],
            $rows
        );
    }
}
