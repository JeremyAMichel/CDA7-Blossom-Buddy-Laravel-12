<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_sync_tracking', function (Blueprint $table) {
            $table->id();
            $table->string('api_name')->default('perenual'); // Pour gérer plusieurs APIs
            $table->integer('last_processed_id')->default(0);
            $table->integer('daily_request_count')->default(0);
            $table->date('last_sync_date');
            $table->integer('total_requests_made')->default(0);
            $table->integer('successful_requests')->default(0);
            $table->integer('failed_requests')->default(0);
            $table->timestamps();
            
            $table->unique(['api_name', 'last_sync_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_sync_tracking');
    }
};