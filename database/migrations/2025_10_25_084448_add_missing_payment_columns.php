<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingPaymentColumns extends Migration
{
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            // Only add columns that don't exist
            if (!Schema::hasColumn('payments', 'payment_method')) {
                $table->enum('payment_method', ['cash', 'pesapal', 'bank_transfer', 'mobile_money'])->default('cash');
            }
            
            if (!Schema::hasColumn('payments', 'reference_number')) {
                $table->string('reference_number')->nullable();
            }
            
            if (!Schema::hasColumn('payments', 'paid_at')) {
                $table->timestamp('paid_at')->nullable();
            }
            
            if (!Schema::hasColumn('payments', 'processed_by')) {
                $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('payments', 'notes')) {
                $table->text('notes')->nullable();
            }
            
            if (!Schema::hasColumn('payments', 'order_tracking_id')) {
                $table->string('order_tracking_id')->nullable();
            }
        });

        // Update existing payments to have default payment_method
        if (Schema::hasColumn('payments', 'payment_method')) {
            \DB::table('payments')->whereNull('payment_method')->update(['payment_method' => 'cash']);
        }
        
        if (Schema::hasColumn('payments', 'paid_at')) {
            \DB::table('payments')->whereNull('paid_at')->update(['paid_at' => \DB::raw('created_at')]);
        }
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            // Only drop columns that were added by this migration
            $columnsToDrop = [];
            
            if (Schema::hasColumn('payments', 'payment_method')) {
                $columnsToDrop[] = 'payment_method';
            }
            
            if (Schema::hasColumn('payments', 'reference_number')) {
                $columnsToDrop[] = 'reference_number';
            }
            
            if (Schema::hasColumn('payments', 'paid_at')) {
                $columnsToDrop[] = 'paid_at';
            }
            
            if (Schema::hasColumn('payments', 'processed_by')) {
                $table->dropForeign(['processed_by']);
                $columnsToDrop[] = 'processed_by';
            }
            
            if (Schema::hasColumn('payments', 'notes')) {
                $columnsToDrop[] = 'notes';
            }
            
            if (Schema::hasColumn('payments', 'order_tracking_id')) {
                $columnsToDrop[] = 'order_tracking_id';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
}