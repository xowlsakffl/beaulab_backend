<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('harmful_words', function (Blueprint $table): void {
            $table->id();
            $table->string('word', 50);
            $table->string('normalized_word', 100)->collation('utf8mb4_bin')->unique();
            $table->string('matching_mode', 20)->default('CONTAINS');
            $table->boolean('detect_variants')->default(false);
            $table->string('status', 20)->default('ACTIVE')->index();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
        });
        Schema::create('harmful_word_scopes', function (Blueprint $table): void {
            $table->foreignId('harmful_word_id')->constrained()->cascadeOnDelete();
            $table->string('scope', 30);
            $table->primary(['harmful_word_id', 'scope']);
            $table->index(['scope', 'harmful_word_id']);
        });
        Schema::create('harmful_word_exceptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('harmful_word_id')->constrained()->cascadeOnDelete();
            $table->string('phrase', 100);
            $table->string('normalized_phrase', 200)->collation('utf8mb4_bin');
            $table->unique(['harmful_word_id', 'normalized_phrase'], 'harmful_word_exception_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harmful_word_exceptions');
        Schema::dropIfExists('harmful_word_scopes');
        Schema::dropIfExists('harmful_words');
    }
};
