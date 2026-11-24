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
        // 1. Enhance order_bookings to hold all 14,868 legacy booking records
        Schema::table('order_bookings', function (Blueprint $table) {
            $table->dropForeign('fk_booking_mctype');
            $table->unsignedInteger('machine_type_id')->nullable()->change();
            $table->string('no_booking', 100)->nullable()->after('booking_code');
            $table->string('no_order', 255)->nullable()->after('no_booking');
            $table->string('needle', 50)->nullable()->after('machine_type_id');
            $table->string('seam', 50)->nullable()->after('needle');
            $table->unsignedInteger('sisa_pcs')->default(0)->after('estimated_pcs');
            $table->date('delivery_date')->nullable()->after('target_month');
            $table->unsignedInteger('lead_time_days')->default(0)->after('delivery_date');
            $table->string('legacy_status', 50)->nullable()->after('status');
        });

        // 2. Add order_booking_cancellations for data_cancel (4,044 rows)
        Schema::create('order_booking_cancellations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_booking_id');
            $table->unsignedInteger('qty_cancel_pcs')->default(0);
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('order_booking_id')->references('id')->on('order_bookings')->cascadeOnDelete();
        });

        // 3. Enhance flow_steps to capture exact factory area routing
        Schema::table('flow_steps', function (Blueprint $table) {
            $table->string('department_type', 50)->nullable()->change();
            $table->string('area_code', 50)->nullable()->after('department_type');
            $table->unsignedInteger('factory_area_id')->nullable()->after('area_code');

            $table->foreign('factory_area_id')->references('id')->on('factory_areas')->nullOnDelete();
        });

        // 4. Add machine_service_records for data_service (907 rows)
        Schema::create('machine_service_records', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('machine_id')->nullable();
            $table->date('service_date');
            $table->string('jarum', 50)->nullable();
            $table->string('technician_name', 100)->nullable();
            $table->string('inspector_name', 100)->nullable();
            $table->string('area_head', 100)->nullable();
            $table->unsignedInteger('factory_area_id')->nullable();
            $table->unsignedSmallInteger('machine_year')->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('machine_id')->references('id')->on('machines')->nullOnDelete();
            $table->foreign('factory_area_id')->references('id')->on('factory_areas')->nullOnDelete();
            $table->index(['machine_id', 'service_date'], 'idx_mc_service_date');
        });

        // 5. Add machine_service_checklists for data_maintenance (15,286 rows)
        Schema::create('machine_service_checklists', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('machine_service_record_id');
            $table->unsignedInteger('master_maintenance_id')->nullable();
            $table->string('item_name', 255);
            $table->enum('status', ['OK', 'NOT_OK', 'NA'])->default('NA');
            $table->string('sparepart_name', 255)->nullable();
            $table->unsignedInteger('sparepart_qty')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('machine_service_record_id')->references('id')->on('machine_service_records')->cascadeOnDelete();
        });

        // 6. Add machine_waste_logs for bs_mesin (3.18 juta log waste mesin per operator & shift)
        Schema::create('machine_waste_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('production_date');
            $table->unsignedInteger('machine_id')->nullable();
            $table->unsignedInteger('factory_area_id')->nullable();
            $table->string('employee_id', 50)->nullable();
            $table->string('employee_name', 100)->nullable();
            $table->string('shift', 10)->nullable();
            $table->string('style_code', 50)->nullable();
            $table->string('size', 50)->nullable();
            $table->string('inisial', 50)->nullable();
            $table->decimal('qty_pcs', 10, 2)->default(0.00);
            $table->decimal('weight_gram', 10, 2)->default(0.00);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('machine_id')->references('id')->on('machines')->nullOnDelete();
            $table->foreign('factory_area_id')->references('id')->on('factory_areas')->nullOnDelete();
            $table->index(['machine_id', 'production_date'], 'idx_waste_mc_date');
            $table->index('production_date', 'idx_waste_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machine_waste_logs');
        Schema::dropIfExists('machine_service_checklists');
        Schema::dropIfExists('machine_service_records');

        Schema::table('flow_steps', function (Blueprint $table) {
            $table->dropForeign(['factory_area_id']);
            $table->dropColumn(['factory_area_id', 'area_code']);
        });

        Schema::dropIfExists('order_booking_cancellations');

        Schema::table('order_bookings', function (Blueprint $table) {
            $table->dropColumn(['legacy_status', 'lead_time_days', 'delivery_date', 'sisa_pcs', 'seam', 'needle', 'no_order', 'no_booking']);
            $table->foreign('machine_type_id', 'fk_booking_mctype')->references('id')->on('machine_types')->onDelete('restrict');
        });
    }
};
