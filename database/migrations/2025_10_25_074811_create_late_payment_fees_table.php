<?php
// database/migrations/2024_01_04_create_late_payment_fees_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLatePaymentFeesTable extends Migration
{
    public function up()
    {
        Schema::create('late_payment_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartment_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('month'); // YYYY-MM format
            $table->decimal('amount', 12, 2);
            $table->decimal('original_rent', 12, 2);
            $table->date('due_date');
            $table->enum('status', ['paid', 'unpaid'])->default('unpaid');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['apartment_id', 'month']); // One late fee per apartment per month
        });
    }

    public function down()
    {
        Schema::dropIfExists('late_payment_fees');
    }
}