<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExpensesTable extends Migration
{
    public function up()
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->enum('type', ['operating', 'administrative', 'maintenance', 'other']);
            $table->enum('category', ['utilities', 'salaries', 'office_supplies', 'maintenance', 'insurance', 'taxes', 'other']);
            $table->date('date');
            $table->string('payment_method');
            $table->string('reference')->nullable();
            $table->enum('status', ['paid', 'unpaid'])->default('paid');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('expenses');
    }
}