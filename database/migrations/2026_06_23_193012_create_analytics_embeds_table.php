<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_embeds', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('dashboard_id')
                ->constrained('analytics_dashboards')
                ->cascadeOnDelete();

            $table->string('name');
            $table->integer('default_ttl_seconds')->default(300);
            $table->json('theme')->nullable();
            $table->json('default_filters')->nullable();

            $table->boolean('is_enabled')->default(true);

            $table->timestamps();

            $table->index(['organization_id', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_embeds');
    }
};
