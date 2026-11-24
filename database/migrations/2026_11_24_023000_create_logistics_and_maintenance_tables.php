<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Saldo Benang di Lantai Rajut (Yarn Stock Balances)
        Schema::create('yarn_stock_balances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('factory_area_id');
            $table->string('yarn_code', 50)->comment('Kode spesifikasi benang');
            $table->string('yarn_name', 100);
            $table->string('color_name', 50);
            $table->string('lot_number', 50);
            $table->unsignedInteger('qty_cones')->default(0);
            $table->decimal('weight_kg', 10, 3)->default(0.000);
            $table->timestamps();

            $table->foreign('factory_area_id', 'fk_yarn_stock_area')->references('id')->on('factory_areas')->cascadeOnDelete();
            $table->unique(['factory_area_id', 'yarn_code', 'lot_number'], 'uk_yarn_area_lot');
        });

        // 2. Mutasi Keluar/Masuk Benang Lantai (Yarn Transactions)
        Schema::create('yarn_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('yarn_stock_balance_id')->constrained('yarn_stock_balances')->cascadeOnDelete();
            $table->enum('transaction_type', ['RECEIPT_FROM_WH', 'RETURN_TO_WH', 'USAGE_KNITTING', 'ADJUSTMENT']);
            $table->integer('cones_delta');
            $table->decimal('weight_kg_delta', 10, 3);
            $table->string('reference_number', 50)->nullable()->comment('No Surat Jalan / PDK');
            $table->foreignId('performed_by')->constrained('users')->onDelete('restrict');
            $table->timestamp('transaction_time')->useCurrent();
        });

        // 3. Pencatatan Kejadian Kerusakan Mesin (Machine Downtimes)
        Schema::create('machine_downtimes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('machine_id');
            $table->unsignedInteger('downtime_reason_id');
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->unsignedInteger('duration_minutes')->virtualAs('CASE WHEN end_time IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, start_time, end_time) ELSE NULL END')->nullable();
            $table->foreignId('reported_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('action_taken')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('machine_id', 'fk_downtime_machine')->references('id')->on('machines')->cascadeOnDelete();
            $table->foreign('downtime_reason_id', 'fk_downtime_reason')->references('id')->on('downtime_reasons')->onDelete('restrict');
            $table->index(['machine_id', 'start_time'], 'idx_downtime_machine_time');
        });

        // 4. Penggunaan Jarum Mesin Rajut (Needle Consumptions)
        Schema::create('needle_consumptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('machine_id');
            $table->date('replacement_date');
            $table->unsignedTinyInteger('shift');
            $table->string('needle_type', 50)->comment('Tipe/Kode jarum rajut');
            $table->unsignedSmallInteger('broken_count')->default(1);
            $table->unsignedSmallInteger('replaced_count')->default(1);
            $table->enum('reason', ['BENT', 'BROKEN_HOOK', 'WEAR_TEAR', 'CRASH'])->default('BROKEN_HOOK');
            $table->foreignId('mechanic_id')->constrained('users')->onDelete('restrict');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('machine_id', 'fk_needle_machine')->references('id')->on('machines')->cascadeOnDelete();
            $table->index(['machine_id', 'replacement_date'], 'idx_needle_mc_date');
        });

        // 5. Tiket Pengaduan & Service Mesin (Maintenance Tickets)
        Schema::create('maintenance_tickets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ticket_number', 30)->unique();
            $table->unsignedInteger('machine_id');
            $table->enum('priority', ['LOW', 'MEDIUM', 'HIGH', 'URGENT'])->default('MEDIUM');
            $table->text('issue_description');
            $table->foreignId('reported_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['OPEN', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'])->default('OPEN');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();

            $table->foreign('machine_id', 'fk_ticket_machine')->references('id')->on('machines')->cascadeOnDelete();
            $table->index(['status', 'priority'], 'idx_ticket_status');
        });

        // 6. Percakapan / Update Progres Tiket (Maintenance Ticket Replies)
        Schema::create('maintenance_ticket_replies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('maintenance_ticket_id')->constrained('maintenance_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->text('message');
            $table->timestamp('created_at')->useCurrent();
        });

        // 7. Audit Log Sistem Enterprise (Audit Logs)
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50)->comment('CREATE, UPDATE, DELETE, REVISE, LOGIN');
            $table->string('table_name', 50);
            $table->unsignedBigInteger('record_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['table_name', 'record_id'], 'idx_audit_table_record');
            $table->index(['user_id', 'action', 'created_at'], 'idx_audit_user_action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('maintenance_ticket_replies');
        Schema::dropIfExists('maintenance_tickets');
        Schema::dropIfExists('needle_consumptions');
        Schema::dropIfExists('machine_downtimes');
        Schema::dropIfExists('yarn_transactions');
        Schema::dropIfExists('yarn_stock_balances');
    }
};
