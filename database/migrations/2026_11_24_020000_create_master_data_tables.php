<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Master Pabrik (Plants)
        Schema::create('plants', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->text('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->timestamps();
        });

        // 2. Master Area Pabrik / Departemen (Factory Areas)
        Schema::create('factory_areas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('plant_id');
            $table->string('area_code', 20);
            $table->string('area_name', 100);
            $table->enum('department_type', ['KNITTING', 'LINKING', 'SETTING', 'PACKING', 'WAREHOUSE', 'QC']);
            $table->timestamps();

            $table->foreign('plant_id', 'fk_factory_areas_plant')->references('id')->on('plants')->onDelete('restrict');
            $table->unique(['plant_id', 'area_code'], 'uk_plant_area_code');
        });

        // 3. Akses Pengguna ke Area Tertentu (User Area Access)
        Schema::create('user_area_access', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('factory_area_id');
            $table->boolean('can_write')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('factory_area_id', 'fk_uaa_area')->references('id')->on('factory_areas')->cascadeOnDelete();
            $table->unique(['user_id', 'factory_area_id'], 'uk_user_area');
        });

        // 4. Master Tipe Mesin & Silinder (Machine Types)
        Schema::create('machine_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('brand', 50);
            $table->string('model_name', 50);
            $table->decimal('cylinder_inches', 4, 2)->comment('Contoh: 3.50, 3.75, 4.00');
            $table->unsignedInteger('needle_count')->comment('Jumlah jarum: 96, 108, 120, 144, 168, 200');
            $table->unsignedInteger('standard_speed_rpm')->default(280);
            $table->timestamps();

            $table->index(['cylinder_inches', 'needle_count'], 'idx_machine_type_specs');
        });

        // 5. Master Fisik 3.000+ Mesin (Machines)
        Schema::create('machines', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('machine_type_id');
            $table->unsignedInteger('factory_area_id');
            $table->string('machine_number', 20)->unique()->comment('Identitas fisik, contoh MC-001, MC-102');
            $table->string('serial_number', 50)->nullable();
            $table->enum('status', ['RUNNING', 'IDLE', 'BREAKDOWN', 'MAINTENANCE', 'OFFLINE'])->default('IDLE');
            $table->timestamps();

            $table->foreign('machine_type_id', 'fk_machines_type')->references('id')->on('machine_types')->onDelete('restrict');
            $table->foreign('factory_area_id', 'fk_machines_area')->references('id')->on('factory_areas')->onDelete('restrict');
            $table->index(['factory_area_id', 'status'], 'idx_machine_status');
        });

        // 6. Master Kategori Cacat Rajut & Konveksi (Defect Types)
        Schema::create('defect_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('defect_code', 20)->unique();
            $table->string('description', 100);
            $table->enum('category', ['KNITTING_NEEDLE', 'YARN_DEFECT', 'MACHINE_TIMING', 'DIRT_OIL', 'LINKING_DEFECT', 'OTHER']);
            $table->boolean('is_reworkable')->default(true);
            $table->timestamps();
        });

        // 7. Master Kategori Kerusakan & Downtime (Downtime Reasons)
        Schema::create('downtime_reasons', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->enum('category', ['MECHANICAL', 'ELECTRICAL', 'WAITING_YARN', 'WAITING_PPS', 'PREVENTIVE', 'POWER_OUTAGE']);
            $table->timestamp('created_at')->useCurrent();
        });

        // 8. Kalender Libur Perusahaan (Company Holidays)
        Schema::create('company_holidays', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('plant_id')->nullable()->comment('NULL jika berlaku untuk semua plant');
            $table->date('holiday_date');
            $table->string('description', 100);
            $table->boolean('is_working_day')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('plant_id', 'fk_holidays_plant')->references('id')->on('plants')->cascadeOnDelete();
            $table->unique(['plant_id', 'holiday_date'], 'uk_plant_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_holidays');
        Schema::dropIfExists('downtime_reasons');
        Schema::dropIfExists('defect_types');
        Schema::dropIfExists('machines');
        Schema::dropIfExists('machine_types');
        Schema::dropIfExists('user_area_access');
        Schema::dropIfExists('factory_areas');
        Schema::dropIfExists('plants');
    }
};
