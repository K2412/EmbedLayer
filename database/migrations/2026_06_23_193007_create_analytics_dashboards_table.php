<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_dashboards', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('analytics_project_id')
                ->constrained('analytics_projects')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();

            $table->json('theme')->nullable();
            $table->json('default_filters')->nullable();

            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->unique(['analytics_project_id', 'slug']);
            $table->index(['organization_id', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_dashboards');
    }
};
