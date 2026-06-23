<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_semantic_providers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('type'); // internal, cube, dbt_semantic_layer

            $table->foreignUlid('data_source_id')->nullable()
                ->constrained('analytics_data_sources')
                ->nullOnDelete();

            $table->json('encrypted_config')->nullable();
            $table->json('capabilities')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_semantic_providers');
    }
};
