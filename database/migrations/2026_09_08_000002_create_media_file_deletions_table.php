<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_file_deletions', function (Blueprint $table): void {
            $table->id();
            $table->string('disk', 100);
            $table->json('paths');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('available_at')->index();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_file_deletions');
    }
};
