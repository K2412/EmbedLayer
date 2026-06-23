<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_query_runs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('semantic_provider_id')->nullable()
                ->constrained('analytics_semantic_providers')
                ->nullOnDelete();
            $table->foreignUlid('dashboard_id')->nullable()
                ->constrained('analytics_dashboards')
                ->nullOnDelete();
            $table->foreignUlid('chart_id')->nullable()
                ->constrained('analytics_charts')
                ->nullOnDelete();

            $table->string('provider_type')->nullable(); // internal, cube, dbt_semantic_layer
            $table->string('model_name')->nullable();

            $table->string('status'); // ok, error, timeout
            $table->integer('duration_ms')->nullable();
            $table->boolean('cache_hit')->default(false);

            $table->string('cache_key')->nullable();
            $table->string('external_account_id')->nullable();

            $table->json('query_shape')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['dashboard_id', 'chart_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_query_runs');
    }
};
