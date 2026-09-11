<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_promotion_slot_locks', function (Blueprint $table): void {
            $table->enum('side', ['LEFT', 'RIGHT']);
            $table->unsignedTinyInteger('slot');
            $table->primary(['side', 'slot']);
        });

        $slots = [];
        foreach (['LEFT', 'RIGHT'] as $side) {
            foreach ([1, 2, 3] as $slot) {
                $slots[] = compact('side', 'slot');
            }
        }
        DB::table('hospital_promotion_slot_locks')->insert($slots);
        DB::statement('ALTER TABLE hospital_promotion_slot_locks ADD CONSTRAINT hp_lock_slot_check CHECK (slot BETWEEN 1 AND 3)');

        Schema::create('hospital_promotions', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->longText('content');
            $table->enum('side', ['LEFT', 'RIGHT']);
            $table->unsignedTinyInteger('slot');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('INACTIVE');
            $table->unsignedBigInteger('click_count')->default(0);
            $table->foreignId('created_by_staff_id')->nullable()->constrained('account_staffs')->nullOnDelete();
            $table->foreignId('updated_by_staff_id')->nullable()->constrained('account_staffs')->nullOnDelete();
            $table->timestamps(6);
            $table->foreign(['side', 'slot'], 'hp_slot_fk')->references(['side', 'slot'])->on('hospital_promotion_slot_locks')->restrictOnDelete()->restrictOnUpdate();
            $table->index(['side', 'slot', 'start_date', 'end_date'], 'hp_overlap_idx');
            $table->index(['side', 'start_date', 'slot', 'id'], 'hp_upcoming_idx');
            $table->index(['end_date', 'id'], 'hp_ended_idx');
            $table->index(['status', 'start_date', 'end_date'], 'hp_public_current_idx');
        });
        DB::statement('ALTER TABLE hospital_promotions ADD CONSTRAINT hp_dates_check CHECK (start_date >= \'1000-01-01\' AND start_date <= end_date)');
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_promotions');
        Schema::dropIfExists('hospital_promotion_slot_locks');
    }
};
