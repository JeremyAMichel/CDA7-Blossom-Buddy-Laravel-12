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
        Schema::table('plants', function (Blueprint $table) {
            $table->string('api_id')->nullable();
            $table->string('watering')->nullable();
            $table->string('watering_period')->nullable();
            $table->boolean('flowers')->default(false);
            $table->boolean('fruits')->default(false);
            $table->boolean('leaf')->default(false);
            $table->string('growth_rate')->nullable();
            $table->string('maintenance')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plants', function (Blueprint $table) {
            $table->dropColumn([
                'api_id',
                'watering',
                'watering_period',
                'flowers',
                'fruits',
                'leaf',
                'growth_rate',
                'maintenance'
            ]);
        });
    }
};
