<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banner_exposure_placements', function (Blueprint $table): void {
            $table->string('code', 40)->primary();
            $table->json('ordered_keys')->nullable();
            $table->unsignedInteger('revision')->default(0);
        });
        foreach (['MAIN_POPUP', 'MAIN_VERTICAL_BANNER', 'MAIN_HORIZONTAL_BANNER', 'CONSULT_MEMO', 'SEARCH'] as $code) {
            DB::table('banner_exposure_placements')->insert(['code' => $code]);
        }
        Schema::create('banner_exposures', function (Blueprint $table): void {
            $table->id();
            $table->string('placement', 40);
            $table->foreign('placement')->references('code')->on('banner_exposure_placements')->restrictOnDelete();
            $table->string('title');
            $table->string('connection_type', 20);
            $table->string('target_type', 20);
            $table->foreignId('hospital_id')->nullable()->constrained('hospitals')->restrictOnDelete();
            $table->foreignId('hospital_event_id')->nullable()->constrained('hospital_events')->restrictOnDelete();
            $table->foreignId('notice_id')->nullable()->constrained('notices')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->restrictOnDelete();
            $table->string('external_url', 2048)->nullable();
            $table->string('open_mode', 20)->default('IN_APP');
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->string('status', 20)->default('INACTIVE');
            $table->foreignId('created_by_staff_id')->nullable()->constrained('account_staffs')->nullOnDelete();
            $table->timestamps(6);
            $table->softDeletes();
            $table->index(['placement', 'deleted_at', 'status', 'start_at', 'end_at'], 'banner_exposures_schedule_idx');
        });
        Schema::create('banner_exposure_metrics', function (Blueprint $table): void {
            $table->string('source_key', 40)->primary();
            $table->unsignedBigInteger('click_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banner_exposure_metrics');
        Schema::dropIfExists('banner_exposures');
        Schema::dropIfExists('banner_exposure_placements');
    }
};
