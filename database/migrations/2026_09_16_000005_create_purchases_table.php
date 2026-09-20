<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->string('reference_no', 60)->unique();
            $table->date('purchase_date');
            $table->decimal('total_cost', 14, 2)->default(0.00);
            $table->decimal('paid_amount', 14, 2)->default(0.00);
            $table->decimal('due_amount', 14, 2)->default(0.00);
            $table->enum('payment_status', ['paid', 'partial', 'due'])->default('due');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};

