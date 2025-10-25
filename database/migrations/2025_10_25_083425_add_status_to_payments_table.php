<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('status', ['pending', 'paid', 'failed'])->default('paid');
            $table->timestamp('paid_at')->nullable();
        });

        // Update existing payments to have status 'paid'
        DB::table('payments')->update(['status' => 'paid']);
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['status', 'paid_at']);
        });
    }
};
