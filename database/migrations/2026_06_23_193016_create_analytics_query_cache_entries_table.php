<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_query_cache_entries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();

            $table->string('cache_key')->unique();

            $table->json('result');
            $table->json('metadata')->nullable();

            $table->timestamp('expires_at');
            $table->timestamp('last_accessed_at')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_query_cache_entries');
    }
};
