<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_dashboard_tabs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('dashboard_id')
                ->constrained('analytics_dashboards')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('slug');
            $table->integer('position')->default(0);

            $table->timestamps();

            $table->unique(['dashboard_id', 'slug']);
            $table->index(['dashboard_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_dashboard_tabs');
    }
};
