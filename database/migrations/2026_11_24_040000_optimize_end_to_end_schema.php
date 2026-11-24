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
        // 1. Optimize order_styles: add inisial, seam, factory
        Schema::table('order_styles', function (Blueprint $table) {
            $table->string('inisial', 50)->default('-')->after('style_code');
            $table->string('seam', 50)->nullable()->after('size');
            $table->string('factory', 50)->nullable()->after('seam');

            $table->index(['style_code', 'inisial'], 'idx_order_styles_style_inisial');
            $table->index('inisial', 'idx_order_styles_inisial');
        });

        // 2. Optimize machines: add brand and year_manufactured
        Schema::table('machines', function (Blueprint $table) {
            $table->string('brand', 100)->nullable()->after('machine_number');
            $table->unsignedSmallInteger('year_manufactured')->nullable()->after('brand');

            $table->index('brand', 'idx_machines_brand');
        });

        // 3. Optimize production_boxes: add factory_area_id directly for high-performance area queries
        Schema::table('production_boxes', function (Blueprint $table) {
            $table->unsignedInteger('factory_area_id')->nullable()->after('machine_id');

            $table->foreign('factory_area_id', 'fk_prod_boxes_area')->references('id')->on('factory_areas')->nullOnDelete();
            $table->index(['factory_area_id', 'production_date'], 'idx_prod_area_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_boxes', function (Blueprint $table) {
            $table->dropForeign('fk_prod_boxes_area');
            $table->dropIndex('idx_prod_area_date');
            $table->dropColumn('factory_area_id');
        });

        Schema::table('machines', function (Blueprint $table) {
            $table->dropIndex('idx_machines_brand');
            $table->dropColumn(['year_manufactured', 'brand']);
        });

        Schema::table('order_styles', function (Blueprint $table) {
            $table->dropIndex('idx_order_styles_inisial');
            $table->dropIndex('idx_order_styles_style_inisial');
            $table->dropColumn(['factory', 'seam', 'inisial']);
        });
    }
};
