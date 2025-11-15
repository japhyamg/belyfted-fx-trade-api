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
        Schema::create('market_rates', function (Blueprint $table) {
            $table->id();
            $table->string('pair', 7);
            $table->string('base_currency', 3);
            $table->string('quote_currency', 3);
            $table->decimal('rate', 18, 8);
            $table->decimal('bid', 18, 8)->nullable();
            $table->decimal('ask', 18, 8)->nullable();
            $table->timestamps();

            $table->unique('pair');
            $table->index(['base_currency', 'quote_currency']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_rates');
    }
};
