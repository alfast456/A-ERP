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
        // 1. Transaksi Output Fisik Box Rajut (Production Boxes)
        // Primary key komposit menyertakan production_date untuk partisi.
        Schema::create('production_boxes', function (Blueprint $table) {
            $table->bigInteger('id')->unsigned()->autoIncrement();
            $table->string('box_barcode', 64)->comment('Barcode unik fisik box kaos kaki');
            $table->date('production_date');
            $table->unsignedTinyInteger('shift')->comment('1, 2, atau 3');
            $table->unsignedBigInteger('order_shipment_id');
            $table->unsignedInteger('machine_id');
            $table->unsignedBigInteger('operator_id');
            $table->unsignedSmallInteger('good_pairs')->default(0)->comment('Pasang lolos QC');
            $table->unsignedSmallInteger('defect_pairs')->default(0)->comment('Pasang BS / cacat');
            $table->decimal('weight_kg', 6, 3)->default(0.000);
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['id', 'production_date']);
            $table->unique(['box_barcode', 'production_date'], 'uk_box_barcode_date');
            $table->index('order_shipment_id', 'idx_prod_shipment');
            $table->index(['machine_id', 'production_date'], 'idx_prod_machine_date');
            $table->index('operator_id', 'idx_prod_operator');
        });

        // 2. Detail Cacat per Box Hasil Rajutan (Box Defects)
        Schema::create('box_defects', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('production_box_id');
            $table->unsignedInteger('defect_type_id');
            $table->unsignedSmallInteger('defect_count_pairs')->default(1);
            $table->string('notes', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('defect_type_id', 'fk_box_defects_type')->references('id')->on('defect_types')->onDelete('restrict');
            $table->index('production_box_id', 'idx_box_defects_box');
        });

        // 3. Siklus Jalur Perbaikan Kaos Kaki Cacat (Rework Tracking)
        Schema::create('rework_tracking', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('production_box_id');
            $table->unsignedInteger('defect_type_id');
            $table->unsignedSmallInteger('sent_pairs');
            $table->unsignedSmallInteger('repaired_pairs')->default(0)->comment('Sukses diperbaiki');
            $table->unsignedSmallInteger('scrapped_pairs')->default(0)->comment('BS murni tidak tertolong');
            $table->enum('status', ['IN_REPAIR', 'RESOLVED', 'SCRAPPED'])->default('IN_REPAIR');
            $table->unsignedInteger('repair_area_id');
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('repaired_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('defect_type_id', 'fk_rework_defect_type')->references('id')->on('defect_types')->onDelete('restrict');
            $table->foreign('repair_area_id', 'fk_rework_area')->references('id')->on('factory_areas')->onDelete('restrict');
            $table->index(['status', 'repair_area_id'], 'idx_rework_status');
        });

        // 4. Template Jalur Proses / Rute Manufaktur (Flow Templates)
        Schema::create('flow_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100)->comment('Contoh: Standard Crew Sock, Low-Cut Ankle');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });

        // 5. Detail Langkah Alur Rute (Flow Steps)
        Schema::create('flow_steps', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('flow_template_id');
            $table->unsignedTinyInteger('step_order')->comment('1=Knitting, 2=Linking, 3=Setting, etc.');
            $table->enum('department_type', ['KNITTING', 'LINKING', 'SETTING', 'PACKING', 'WAREHOUSE', 'QC']);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('flow_template_id', 'fk_flow_steps_template')->references('id')->on('flow_templates')->cascadeOnDelete();
            $table->unique(['flow_template_id', 'step_order'], 'uk_flow_step_order');
        });

        // 6. Log Audit Perpindahan Fisik Box Antar-Departemen (WIP Movements)
        Schema::create('wip_movements', function (Blueprint $table) {
            $table->bigInteger('id')->unsigned()->autoIncrement();
            $table->date('movement_date');
            $table->unsignedBigInteger('production_box_id');
            $table->unsignedInteger('from_area_id');
            $table->unsignedInteger('to_area_id');
            $table->unsignedInteger('sent_pairs');
            $table->unsignedInteger('received_pairs')->default(0);
            $table->foreignId('dispatched_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['DISPATCHED', 'RECEIVED', 'DISCREPANCY'])->default('DISPATCHED');
            $table->string('remarks', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['id', 'movement_date']);
            $table->foreign('from_area_id', 'fk_wip_from_area')->references('id')->on('factory_areas')->onDelete('restrict');
            $table->foreign('to_area_id', 'fk_wip_to_area')->references('id')->on('factory_areas')->onDelete('restrict');
            $table->index('production_box_id', 'idx_wip_box');
            $table->index(['from_area_id', 'to_area_id'], 'idx_wip_from_to');
            $table->index('status', 'idx_wip_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wip_movements');
        Schema::dropIfExists('flow_steps');
        Schema::dropIfExists('flow_templates');
        Schema::dropIfExists('rework_tracking');
        Schema::dropIfExists('box_defects');
        Schema::dropIfExists('production_boxes');
    }
};
