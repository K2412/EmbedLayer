<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_embed_domains', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('embed_id')
                ->constrained('analytics_embeds')
                ->cascadeOnDelete();

            // V1: exact host match, no wildcards.
            $table->string('host');

            $table->timestamps();

            $table->unique(['embed_id', 'host']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_embed_domains');
    }
};
