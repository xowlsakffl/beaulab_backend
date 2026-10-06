<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_exposure_positions', function (Blueprint $table): void {
            $table->string('key', 80)->primary();
            $table->json('ordered_keys')->nullable();
            $table->unsignedInteger('revision')->default(0);
        });
        foreach (['SURGERY', 'PETIT'] as $key) {
            DB::table('video_exposure_positions')->insert(['key' => $key]);
        }
        Schema::create('video_exposures', function (Blueprint $table): void {
            $table->id();
            $table->string('position_key', 80);
            $table->foreign('position_key')->references('key')->on('video_exposure_positions')->restrictOnDelete();
            $table->foreignId('hospital_video_id')->constrained('hospital_videos')->restrictOnDelete();
            $table->foreignId('created_by_staff_id')->nullable()->constrained('account_staffs')->nullOnDelete();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->string('status', 20)->default('ACTIVE');
            $table->timestamps(6);
            $table->softDeletes();
            $table->index(['position_key', 'status', 'start_at', 'end_at'], 'video_exposures_schedule_idx');
        });
        Schema::create('video_exposure_metrics', function (Blueprint $table): void {
            $table->string('position_key', 80);
            $table->string('source_key', 40);
            $table->unsignedBigInteger('click_count')->default(0);
            $table->primary(['position_key', 'source_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_exposure_metrics');
        Schema::dropIfExists('video_exposures');
        Schema::dropIfExists('video_exposure_positions');
    }
};
