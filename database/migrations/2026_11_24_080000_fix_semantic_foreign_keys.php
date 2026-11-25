<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("SET sql_mode = '';");

        // 1. Perbaiki box_defects: tambahkan relasi SKU shipment asli, buat production_box_id nullable, simpan nomor box fisik
        if (!Schema::hasColumn('box_defects', 'order_shipment_id')) {
            Schema::table('box_defects', function (Blueprint $table) {
                $table->unsignedBigInteger('production_box_id')->nullable()->change();
                $table->foreignId('order_shipment_id')->nullable()->after('id')->constrained('order_shipments')->cascadeOnDelete();
                $table->unsignedInteger('factory_area_id')->nullable()->after('defect_type_id');
                $table->string('no_box', 50)->nullable()->after('factory_area_id');
                $table->string('no_label', 50)->nullable()->after('no_box');
                $table->date('defect_date')->nullable()->after('no_label');

                $table->foreign('factory_area_id', 'fk_box_defects_area')->references('id')->on('factory_areas')->nullOnDelete();
                $table->index(['order_shipment_id', 'no_box'], 'idx_box_defects_shipment_box');
            });
        }

        // 2. Perbaiki rework_tracking: tambahkan relasi SKU shipment asli, buat production_box_id nullable, simpan info mesin & box
        if (!Schema::hasColumn('rework_tracking', 'order_shipment_id')) {
            Schema::table('rework_tracking', function (Blueprint $table) {
                $table->unsignedBigInteger('production_box_id')->nullable()->change();
                $table->foreignId('order_shipment_id')->nullable()->after('id')->constrained('order_shipments')->cascadeOnDelete();
                $table->unsignedInteger('machine_id')->nullable()->after('repair_area_id');
                $table->string('no_box', 50)->nullable()->after('machine_id');
                $table->string('no_label', 50)->nullable()->after('no_box');
                $table->date('rework_date')->nullable()->after('no_label');

                $table->foreign('machine_id', 'fk_rework_machine')->references('id')->on('machines')->nullOnDelete();
                $table->index(['order_shipment_id', 'no_box'], 'idx_rework_shipment_box');
            });
        }

        // 3. Tabel Beban Kapasitas Harian (Daily Planning Loads - pemetaan akurat tanggal_planning)
        if (!Schema::hasTable('daily_planning_loads')) {
            Schema::create('daily_planning_loads', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('planning_allocation_id')->constrained('planning_allocations')->cascadeOnDelete();
                $table->date('plan_date');
                $table->unsignedSmallInteger('planned_machine_count')->default(0)->comment('Kapasitas jumlah mesin yang dialokasikan');
                $table->unsignedInteger('daily_target_pcs')->default(0);
                $table->date('start_date')->nullable();
                $table->date('stop_date')->nullable();
                $table->timestamps();

                $table->index(['planning_allocation_id', 'plan_date'], 'idx_daily_plan_alloc_date');
                $table->index('plan_date', 'idx_daily_plan_date');
            });
        }

        // 4. Perbaiki machine_assignments: sesuaikan untuk penugasan fisik per unit mesin dari mesin_pernomor
        if (!Schema::hasColumn('machine_assignments', 'start_time')) {
            Schema::table('machine_assignments', function (Blueprint $table) {
                $table->dateTime('start_time')->nullable()->after('plan_date');
                $table->dateTime('stop_time')->nullable()->after('start_time');
                $table->date('plan_date')->nullable()->change();
            });
        }

        // 5. Perbaiki sample_approvals: bersihkan tanggal invalid, buat machine_id nullable, simpan shipment SKU & mechanic
        DB::statement("UPDATE sample_approvals SET approved_at = NULL WHERE CAST(approved_at AS CHAR) LIKE '0000%'");

        Schema::table('sample_approvals', function (Blueprint $table) {
            $table->unsignedInteger('machine_id')->nullable()->change();
            if (!Schema::hasColumn('sample_approvals', 'order_shipment_id')) {
                $table->foreignId('order_shipment_id')->nullable()->after('order_style_id');
            }
            if (!Schema::hasColumn('sample_approvals', 'mechanic_name')) {
                $table->string('mechanic_name', 100)->nullable()->after('sample_stage');
            }
        });

        try {
            Schema::table('sample_approvals', function (Blueprint $table) {
                $table->foreign('order_shipment_id')->references('id')->on('order_shipments')->nullOnDelete();
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('sample_approvals', function (Blueprint $table) {
                $table->foreign('machine_id', 'fk_pps_machine')->references('id')->on('machines')->nullOnDelete();
            });
        } catch (\Throwable $e) {}

        // 6. Perbaiki audit_logs: tambahkan actor_name asli dari legacy
        if (!Schema::hasColumn('audit_logs', 'actor_name')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->string('actor_name', 100)->nullable()->after('user_id');
                $table->index('actor_name', 'idx_audit_actor_name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('audit_logs', 'actor_name')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropIndex('idx_audit_actor_name');
                $table->dropColumn('actor_name');
            });
        }

        Schema::table('sample_approvals', function (Blueprint $table) {
            try { $table->dropForeign('fk_pps_machine'); } catch (\Throwable $e) {}
            try { $table->dropForeign(['order_shipment_id']); } catch (\Throwable $e) {}
            if (Schema::hasColumn('sample_approvals', 'order_shipment_id')) {
                $table->dropColumn('order_shipment_id');
            }
            if (Schema::hasColumn('sample_approvals', 'mechanic_name')) {
                $table->dropColumn('mechanic_name');
            }
        });

        if (Schema::hasColumn('machine_assignments', 'start_time')) {
            Schema::table('machine_assignments', function (Blueprint $table) {
                $table->dropColumn(['start_time', 'stop_time']);
            });
        }

        Schema::dropIfExists('daily_planning_loads');

        if (Schema::hasColumn('rework_tracking', 'order_shipment_id')) {
            Schema::table('rework_tracking', function (Blueprint $table) {
                try { $table->dropForeign('fk_rework_machine'); } catch (\Throwable $e) {}
                try { $table->dropForeign(['order_shipment_id']); } catch (\Throwable $e) {}
                try { $table->dropIndex('idx_rework_shipment_box'); } catch (\Throwable $e) {}
                $table->dropColumn(['order_shipment_id', 'machine_id', 'no_box', 'no_label', 'rework_date']);
            });
        }

        if (Schema::hasColumn('box_defects', 'order_shipment_id')) {
            Schema::table('box_defects', function (Blueprint $table) {
                try { $table->dropForeign('fk_box_defects_area'); } catch (\Throwable $e) {}
                try { $table->dropForeign(['order_shipment_id']); } catch (\Throwable $e) {}
                try { $table->dropIndex('idx_box_defects_shipment_box'); } catch (\Throwable $e) {}
                $table->dropColumn(['order_shipment_id', 'factory_area_id', 'no_box', 'no_label', 'defect_date']);
            });
        }
    }
};
