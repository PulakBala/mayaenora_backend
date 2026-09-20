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
        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('identifier', 50); // Phone or Email
            $table->string('otp_code', 10);
            $table->enum('purpose', ['registration', 'login', 'password_reset', 'checkout']);
            $table->timestamp('expires_at');
            $table->boolean('is_used')->default(false);
            $table->timestamp('created_at')->nullable();

            $table->index(['identifier', 'otp_code'], 'idx_otp_identifier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_verifications');
    }
};

