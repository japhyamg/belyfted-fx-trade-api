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
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_account_id')->constrained('accounts');
            $table->foreignId('to_account_id')->nullable()->constrained('accounts');
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->decimal('from_amount', 18, 8);
            $table->decimal('to_amount', 18, 8);
            $table->decimal('rate', 18, 8);
            $table->enum('side', ['BUY', 'SELL']);
            $table->enum('status', ['PENDING', 'EXECUTED', 'FAILED', 'CANCELLED'])->default('PENDING');
            $table->string('client_order_id')->unique()->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('from_account_id');
            $table->index('to_account_id');
            $table->index('client_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
