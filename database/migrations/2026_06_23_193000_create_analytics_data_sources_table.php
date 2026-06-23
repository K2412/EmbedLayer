<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_data_sources', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('driver'); // postgres, mysql, bigquery, snowflake, clickhouse

            $table->json('encrypted_config');
            $table->json('capabilities')->nullable();
            $table->json('last_introspected_schema')->nullable();

            $table->timestamp('last_tested_at')->nullable();
            $table->timestamp('last_introspected_at')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'driver']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_data_sources');
    }
};
