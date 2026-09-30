<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remote_consultations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('account_user_id')->nullable()->constrained('account_users')->nullOnDelete();
            $t->string('name', 100);
            $t->date('birth_date');
            $t->enum('gender', ['MALE', 'FEMALE']);
            $t->string('phone', 30);
            $t->enum('surgery_timing', ['WITHIN_1_MONTH', 'WITHIN_2_MONTHS', 'WITHIN_3_MONTHS', 'WITHIN_4_MONTHS', 'WITHIN_6_MONTHS', 'UNDECIDED']);
            $t->unsignedSmallInteger('height_cm')->nullable();
            $t->unsignedBigInteger('budget_amount')->nullable();
            $t->json('special_conditions');
            $t->text('desired_style')->nullable();
            $t->text('inquiry')->nullable();
            $t->json('requested_hospital_ids');
            $t->enum('approval_status', ['PENDING', 'REVIEWING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $t->foreignId('approved_by_staff_id')->nullable()->constrained('account_staffs')->nullOnDelete();
            $t->timestamp('approved_at')->nullable();
            $t->timestamps();
            $t->index(['approval_status', 'created_at', 'id'], 'remote_consultation_status_date');
            $t->index(['created_at', 'id']);
            $t->index('birth_date');
        });
        Schema::create('remote_consultation_categories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('remote_consultation_id')->constrained('remote_consultations')->restrictOnDelete();
            $t->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $t->string('category_name');
            $t->unique(['remote_consultation_id', 'category_id'], 'remote_consultation_category_unique');
        });
        Schema::create('remote_consultation_quotes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('remote_consultation_id')->constrained('remote_consultations')->restrictOnDelete();
            $t->foreignId('hospital_id')->constrained('hospitals')->restrictOnDelete();
            $t->string('hospital_name');
            $t->enum('selection_type', ['SELECTED', 'RANDOM']);
            $t->string('matching_version', 40)->default('random-v1');
            $t->json('matched_categories');
            $t->enum('status', ['WAITING', 'ANSWERED'])->default('WAITING');
            $t->json('quoted_categories')->nullable();
            $t->longText('answer')->nullable();
            $t->unsignedBigInteger('total_amount')->nullable();
            $t->json('recommended_events')->nullable();
            $t->timestamp('answered_at')->nullable();
            $t->timestamps();
            $t->unique(['remote_consultation_id', 'hospital_id'], 'remote_consultation_hospital_unique');
            $t->index(['hospital_id', 'status', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remote_consultation_quotes');
        Schema::dropIfExists('remote_consultation_categories');
        Schema::dropIfExists('remote_consultations');
    }
};
