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
        // 1. Enhance production_boxes: drop unique on barcode, add no_box, no_label, shift_a, shift_b, shift_c
        Schema::table('production_boxes', function (Blueprint $table) {
            $table->dropUnique('uk_box_barcode_date');
            $table->index(['box_barcode', 'production_date'], 'idx_prod_box_barcode_date');

            $table->string('no_box', 100)->nullable()->after('box_barcode');
            $table->unsignedInteger('no_label')->default(1)->after('no_box');
            $table->unsignedInteger('shift_a')->default(0)->after('shift');
            $table->unsignedInteger('shift_b')->default(0)->after('shift_a');
            $table->unsignedInteger('shift_c')->default(0)->after('shift_b');

            $table->index('no_box', 'idx_prod_no_box');
        });

        // 2. Enhance machines: add full jarum specification
        Schema::table('machines', function (Blueprint $table) {
            $table->string('jarum', 50)->nullable()->after('machine_type_id');
            $table->index('jarum', 'idx_machines_jarum');
        });

        // 3. Enhance order_styles: add full jarum / machinetypeid specification
        Schema::table('order_styles', function (Blueprint $table) {
            $table->string('jarum', 50)->nullable()->after('machine_type_id');
            $table->index('jarum', 'idx_order_styles_jarum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_styles', function (Blueprint $table) {
            $table->dropIndex('idx_order_styles_jarum');
            $table->dropColumn('jarum');
        });

        Schema::table('machines', function (Blueprint $table) {
            $table->dropIndex('idx_machines_jarum');
            $table->dropColumn('jarum');
        });

        Schema::table('production_boxes', function (Blueprint $table) {
            $table->dropIndex('idx_prod_no_box');
            $table->dropColumn(['shift_c', 'shift_b', 'shift_a', 'no_label', 'no_box']);
            $table->dropIndex('idx_prod_box_barcode_date');
            $table->unique(['box_barcode', 'production_date'], 'uk_box_barcode_date');
        });
    }
};
