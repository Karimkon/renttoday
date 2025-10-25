<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProcessedByToPaymentsTable extends Migration
{
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'processed_by')) {
                $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            }
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'processed_by')) {
                $table->dropForeign(['processed_by']);
                $table->dropColumn('processed_by');
            }
        });
    }
}