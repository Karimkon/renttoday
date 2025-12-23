<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This table tracks expenses made on behalf of landlords (e.g., painting, repairs)
     * These expenses reduce the amount paid to landlords but do NOT affect commission
     */
    public function up(): void
    {
        Schema::create('landlord_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landlord_id')->constrained()->onDelete('cascade');
            $table->foreignId('apartment_id')->nullable()->constrained()->onDelete('set null');
            $table->string('month', 7); // Format: Y-m (e.g., '2025-12')
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->enum('category', ['maintenance', 'repairs', 'painting', 'utilities', 'taxes', 'insurance', 'other'])->default('other');
            $table->date('expense_date');
            $table->string('reference')->nullable(); // Receipt number, invoice number, etc.
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Index for quick lookups by landlord and month
            $table->index(['landlord_id', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landlord_expenses');
    }
};
