<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLandlordsTable extends Migration
{
    public function up()
    {
        Schema::create('landlords', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->decimal('commission_rate', 5, 2); // 100.00%
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Add landlord_id to apartments table
        Schema::table('apartments', function (Blueprint $table) {
            $table->foreignId('landlord_id')->nullable()->constrained()->onDelete('set null');
            $table->string('location')->nullable(); // Mukono, Bweyogerere, etc.
        });
    }

    public function down()
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->dropForeign(['landlord_id']);
            $table->dropColumn(['landlord_id', 'location']);
        });
        
        Schema::dropIfExists('landlords');
    }
}