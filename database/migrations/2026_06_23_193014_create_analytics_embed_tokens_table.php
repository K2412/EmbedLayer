<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_embed_tokens', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('embed_id')
                ->constrained('analytics_embeds')
                ->cascadeOnDelete();

            $table->string('jti')->unique();
            $table->string('external_account_id')->nullable();
            $table->string('payload_hash');

            $table->timestamp('issued_at');
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();

            $table->index(['embed_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_embed_tokens');
    }
};
