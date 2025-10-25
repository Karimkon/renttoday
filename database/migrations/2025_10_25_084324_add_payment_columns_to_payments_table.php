<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentColumnsToPaymentsTable extends Migration
{
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            // Add new columns for enhanced payment tracking
            $table->enum('payment_method', ['cash', 'pesapal', 'bank_transfer', 'mobile_money'])->default('cash');
            $table->string('reference_number')->nullable();
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('paid');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->string('order_tracking_id')->nullable();
        });

        // Update existing payments to have default values
        DB::table('payments')->update([
            'status' => 'paid',
            'payment_method' => 'cash',
            'paid_at' => DB::raw('created_at')
        ]);
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'reference_number',
                'status',
                'paid_at',
                'processed_by',
                'notes',
                'order_tracking_id'
            ]);
        });
    }
}