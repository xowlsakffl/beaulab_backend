<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nickname_words', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 20);
            $table->string('word', 20);
            $table->string('status', 20)->default('ACTIVE');
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
            $table->unique(['type', 'word']);
            $table->index(['type', 'status', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nickname_words');
    }
};
