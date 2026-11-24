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
        // 1. Kontrak Induk Order / PDK Pembeli (Orders)
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('po_number', 50)->unique()->comment('Nomor PO / PDK Buyer');
            $table->string('buyer_name', 100);
            $table->string('season', 30)->nullable();
            $table->enum('order_type', ['BULK', 'SAMPLE', 'REMAKE', 'PROMO'])->default('BULK');
            $table->unsignedInteger('total_ordered_pairs')->default(0);
            $table->date('order_date');
            $table->enum('status', ['DRAFT', 'CONFIRMED', 'IN_PLANNING', 'IN_PRODUCTION', 'COMPLETED', 'CANCELLED'])->default('CONFIRMED');
            $table->timestamps();

            $table->index(['status', 'order_date'], 'idx_orders_status_date');
        });

        // 2. Detail Varian Style / SKU Produk (Order Styles)
        Schema::create('order_styles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->unsignedInteger('machine_type_id');
            $table->string('style_code', 50);
            $table->string('color_code', 150);
            $table->string('size', 50);
            $table->decimal('smv_seconds', 6, 2)->default(120.00)->comment('SMV detik per pasang');
            $table->decimal('unit_weight_gram', 6, 2)->default(0.00);
            $table->decimal('gw_dz', 6, 2)->default(0.00)->comment('Gross Weight per Lusin');
            $table->timestamps();

            $table->foreign('machine_type_id', 'fk_order_styles_mctype')->references('id')->on('machine_types')->onDelete('restrict');
            $table->index(['style_code', 'color_code', 'size'], 'idx_style_lookup');
        });

        // 3. Jadwal Pengiriman Multi-Delivery (Order Shipments)
        Schema::create('order_shipments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('order_style_id')->constrained('order_styles')->cascadeOnDelete();
            $table->unsignedSmallInteger('shipment_index')->default(1);
            $table->date('target_delivery_date');
            $table->unsignedInteger('qty_pairs');
            $table->timestamps();

            $table->index('target_delivery_date', 'idx_shipment_delivery');
        });

        // 4. Log Audit Pengajuan Revisi Order (Order Revisions)
        Schema::create('order_revisions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->unsignedSmallInteger('revision_number');
            $table->timestamp('revision_date')->useCurrent();
            $table->text('reason_notes');
            $table->foreignId('requested_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('APPROVED');

            $table->unique(['order_id', 'revision_number'], 'uk_order_rev_no');
        });

        // 5. Detail Delta Penurunan / Penambahan Qty Revisi (Order Revision Items)
        Schema::create('order_revision_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('order_revision_id')->constrained('order_revisions')->cascadeOnDelete();
            $table->foreignId('order_shipment_id')->constrained('order_shipments')->cascadeOnDelete();
            $table->unsignedInteger('old_qty');
            $table->unsignedInteger('new_qty');
            $table->integer('delta_qty')->comment('Jika negatif = cancel/penurunan, jika positif = penambahan');
            $table->timestamp('created_at')->useCurrent();
        });

        // 6. Gatekeeper Approval Sampel Pre-Production (Sample Approvals / PPS)
        Schema::create('sample_approvals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('order_style_id')->constrained('order_styles')->cascadeOnDelete();
            $table->unsignedInteger('machine_id');
            $table->enum('sample_stage', ['PROTO', 'FIT', 'PRE_PRODUCTION', 'PILOT'])->default('PRE_PRODUCTION');
            $table->foreignId('mechanic_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('qa_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('merchandiser_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_approved_for_bulk')->default(false);
            $table->text('remarks')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('machine_id', 'fk_pps_machine')->references('id')->on('machines')->onDelete('restrict');
        });

        // 7. Alokasi Beban Order ke Area Pabrik (Planning Allocations)
        Schema::create('planning_allocations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->unsignedInteger('factory_area_id');
            $table->unsignedInteger('allocated_pairs');
            $table->date('target_start_date');
            $table->date('target_end_date');
            $table->timestamps();

            $table->foreign('factory_area_id', 'fk_plan_alloc_area')->references('id')->on('factory_areas')->onDelete('restrict');
        });

        // 8. Jadwal Alokasi Fisik Mesin Harian (Machine Assignments)
        Schema::create('machine_assignments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('planning_allocation_id')->constrained('planning_allocations')->cascadeOnDelete();
            $table->unsignedInteger('machine_id');
            $table->foreignId('order_shipment_id')->constrained('order_shipments')->cascadeOnDelete();
            $table->date('plan_date');
            $table->decimal('target_dz', 8, 2)->default(0.00);
            $table->unsignedInteger('target_pairs');
            $table->timestamps();

            $table->foreign('machine_id', 'fk_mc_assign_machine')->references('id')->on('machines')->onDelete('restrict');
            $table->index(['machine_id', 'plan_date'], 'idx_plan_machine_date');
        });

        // 9. Perhitungan Beban Kapasitas IE (Capacity Forecasts)
        Schema::create('capacity_forecasts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('factory_area_id');
            $table->unsignedInteger('machine_type_id');
            $table->string('forecast_period', 7)->comment('Format YYYY-MM');
            $table->unsignedInteger('active_machines_count');
            $table->decimal('available_hours', 10, 2);
            $table->decimal('required_hours', 10, 2);
            $table->decimal('load_percentage', 5, 2);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('factory_area_id', 'fk_cap_area')->references('id')->on('factory_areas')->cascadeOnDelete();
            $table->foreign('machine_type_id', 'fk_cap_mctype')->references('id')->on('machine_types')->onDelete('restrict');
            $table->unique(['factory_area_id', 'machine_type_id', 'forecast_period'], 'uk_cap_period_type');
        });

        // 10. Target Ekspor & Efisiensi Bulanan (Monthly Targets)
        Schema::create('monthly_targets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('plant_id');
            $table->string('target_month', 7)->comment('Format YYYY-MM');
            $table->unsignedInteger('target_export_pairs')->default(0);
            $table->unsignedInteger('target_local_pairs')->default(0);
            $table->decimal('target_efficiency', 5, 2)->default(85.00);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('plant_id', 'fk_targets_plant')->references('id')->on('plants')->cascadeOnDelete();
            $table->unique(['plant_id', 'target_month'], 'uk_target_plant_month');
        });

        // 11. Antrean Booking Order (Order Bookings)
        Schema::create('order_bookings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('buyer_name', 100);
            $table->string('booking_code', 50)->unique();
            $table->unsignedInteger('machine_type_id');
            $table->unsignedInteger('estimated_pairs');
            $table->string('target_month', 7);
            $table->text('notes')->nullable();
            $table->enum('status', ['HOLD', 'CONFIRMED_TO_ORDER', 'EXPIRED', 'CANCELLED'])->default('HOLD');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('machine_type_id', 'fk_booking_mctype')->references('id')->on('machine_types')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_bookings');
        Schema::dropIfExists('monthly_targets');
        Schema::dropIfExists('capacity_forecasts');
        Schema::dropIfExists('machine_assignments');
        Schema::dropIfExists('planning_allocations');
        Schema::dropIfExists('sample_approvals');
        Schema::dropIfExists('order_revision_items');
        Schema::dropIfExists('order_revisions');
        Schema::dropIfExists('order_shipments');
        Schema::dropIfExists('order_styles');
        Schema::dropIfExists('orders');
    }
};
