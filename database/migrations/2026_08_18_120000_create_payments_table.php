<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->date('paid_at')->nullable();
            $table->string('status')->default('pending'); // pending, paid, late, cancelled
            $table->string('payment_method')->nullable(); // pix, boleto, credit_card, bank_transfer, cash, other
            $table->string('reference_month', 7)->nullable(); // e.g. 2026-08
            $table->string('recurrence')->default('one_time'); // one_time, monthly
            $table->text('notes')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_disk')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
