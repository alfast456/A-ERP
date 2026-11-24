<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Plants
        DB::table('plants')->upsert([
            ['id' => 1, 'code' => 'PLANT-1', 'name' => 'Knitting Plant Utama Cikande', 'address' => 'Kawasan Industri Modern Cikande', 'phone' => '0254-123456'],
        ], ['id'], ['name', 'address', 'phone']);

        // 2. Seed Factory Areas
        DB::table('factory_areas')->upsert([
            ['id' => 1, 'plant_id' => 1, 'area_code' => 'KK9D', 'area_name' => 'Lantai Rajut Gedung 9D', 'department_type' => 'KNITTING'],
            ['id' => 2, 'plant_id' => 1, 'area_code' => 'KK8B', 'area_name' => 'Lantai Rajut Gedung 8B', 'department_type' => 'KNITTING'],
            ['id' => 3, 'plant_id' => 1, 'area_code' => 'ROSSO', 'area_name' => 'Departemen Jahit Rosso & Linking', 'department_type' => 'LINKING'],
            ['id' => 4, 'plant_id' => 1, 'area_code' => 'SETTING', 'area_name' => 'Departemen Boarding & Setting Oven', 'department_type' => 'SETTING'],
            ['id' => 5, 'plant_id' => 1, 'area_code' => 'PACKING', 'area_name' => 'Departemen Final Inspection & Packing', 'department_type' => 'PACKING'],
            ['id' => 6, 'plant_id' => 1, 'area_code' => 'GUDANG', 'area_name' => 'Gudang Finished Goods & Benang', 'department_type' => 'WAREHOUSE'],
        ], ['id'], ['area_name', 'department_type']);

        // 3. Seed Machine Types
        DB::table('machine_types')->upsert([
            ['id' => 1, 'brand' => 'LONATI', 'model_name' => 'GL616F', 'cylinder_inches' => 3.50, 'needle_count' => 144, 'standard_speed_rpm' => 280],
            ['id' => 2, 'brand' => 'LONATI', 'model_name' => 'GL616F', 'cylinder_inches' => 3.75, 'needle_count' => 168, 'standard_speed_rpm' => 280],
            ['id' => 3, 'brand' => 'SANTONI', 'model_name' => 'HT50', 'cylinder_inches' => 3.75, 'needle_count' => 120, 'standard_speed_rpm' => 300],
            ['id' => 4, 'brand' => 'DAKONG', 'model_name' => 'DK-B304', 'cylinder_inches' => 3.50, 'needle_count' => 96, 'standard_speed_rpm' => 260],
        ], ['id'], ['model_name', 'cylinder_inches', 'needle_count', 'standard_speed_rpm']);

        // 4. Seed Defect Types
        DB::table('defect_types')->upsert([
            ['id' => 1, 'defect_code' => 'BS-BOLONG', 'description' => 'Lubang/Bolong pada rajutan kain', 'category' => 'KNITTING_NEEDLE', 'is_reworkable' => true],
            ['id' => 2, 'defect_code' => 'BS-GARIS', 'description' => 'Garis vertikal akibat jarum cacat/patah', 'category' => 'KNITTING_NEEDLE', 'is_reworkable' => false],
            ['id' => 3, 'defect_code' => 'BS-MINYAK', 'description' => 'Tercemar pelumas oli mesin', 'category' => 'DIRT_OIL', 'is_reworkable' => true],
            ['id' => 4, 'defect_code' => 'BS-BELANG', 'description' => 'Perbedaan lot warna/gradasi benang', 'category' => 'YARN_DEFECT', 'is_reworkable' => false],
            ['id' => 5, 'defect_code' => 'BS-ROSSO', 'description' => 'Jahitan linking ujung jari lepas/meleset', 'category' => 'LINKING_DEFECT', 'is_reworkable' => true],
        ], ['id'], ['description', 'category', 'is_reworkable']);

        // 5. Seed Downtime Reasons
        DB::table('downtime_reasons')->upsert([
            ['id' => 1, 'code' => 'DT-NEEDLE', 'name' => 'Ganti jarum patah / lidah bengkok', 'category' => 'MECHANICAL'],
            ['id' => 2, 'code' => 'DT-TIMING', 'name' => 'Sinkronisasi timing silinder dan feeder', 'category' => 'MECHANICAL'],
            ['id' => 3, 'code' => 'DT-YARN-WAIT', 'name' => 'Menunggu pasokan benang dari rak lantai', 'category' => 'WAITING_YARN'],
            ['id' => 4, 'code' => 'DT-PPS-WAIT', 'name' => 'Menunggu persetujuan sampel PPS oleh QA/MD', 'category' => 'WAITING_PPS'],
            ['id' => 5, 'code' => 'DT-POWER', 'name' => 'Pemadaman listrik PLN / Genset', 'category' => 'POWER_OUTAGE'],
        ], ['id'], ['name', 'category']);

        // 6. Seed Roles & Spatie Permissions
        $roles = ['ADMIN', 'PLANNER', 'SUPERVISOR', 'OPERATOR', 'MECHANIC', 'QA', 'WAREHOUSE'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // 7. Seed Initial Core Users
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'System Administrator',
                'email' => 'admin@a-erp.local',
                'password' => Hash::make('password'),
                'role' => 'ADMIN',
                'is_active' => true,
            ]
        );
        $admin->syncRoles(['ADMIN']);

        $planner = User::firstOrCreate(
            ['username' => 'planner'],
            [
                'name' => 'PPIC Planner Lead',
                'email' => 'planner@a-erp.local',
                'password' => Hash::make('password'),
                'role' => 'PLANNER',
                'is_active' => true,
            ]
        );
        $planner->syncRoles(['PLANNER']);

        $spv = User::firstOrCreate(
            ['username' => 'spv_kk9d'],
            [
                'name' => 'Supervisor KK9D',
                'email' => 'spv9d@a-erp.local',
                'password' => Hash::make('password'),
                'role' => 'SUPERVISOR',
                'is_active' => true,
            ]
        );
        $spv->syncRoles(['SUPERVISOR']);

        // Grant SPV access to KK9D
        DB::table('user_area_access')->upsert([
            ['user_id' => $spv->id, 'factory_area_id' => 1, 'can_write' => true],
        ], ['user_id', 'factory_area_id'], ['can_write']);
    }
}
