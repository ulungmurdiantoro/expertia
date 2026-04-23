<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tracking_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('article_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type');
            $table->string('event_context')->nullable();
            $table->string('session_id')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->index(['event_type', 'occurred_at']);
            $table->index(['article_id', 'occurred_at']);
            $table->index(['user_id', 'occurred_at']);
        });

        Schema::create('recommendation_seeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->string('source');
            $table->decimal('score', 12, 4)->default(0);
            $table->string('reason')->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'article_id', 'source']);
            $table->index(['source', 'score']);
            $table->index(['user_id', 'score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recommendation_seeds');
        Schema::dropIfExists('tracking_events');
    }
};
