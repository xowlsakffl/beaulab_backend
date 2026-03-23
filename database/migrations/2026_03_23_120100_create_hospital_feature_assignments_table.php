<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_feature_assignments', function (Blueprint $table) {
            $table->id()->comment('병원 특징 연결 고유 ID');
            $table->foreignId('hospital_id')->comment('병원 ID')->constrained('hospitals')->cascadeOnDelete();
            $table->foreignId('hospital_feature_id')->comment('병원 특징 ID')->constrained('hospital_features')->restrictOnDelete();
            $table->timestamps();

            $table->unique(
                ['hospital_id', 'hospital_feature_id'],
                'hospital_feature_assignments_hospital_feature_unique'
            );
            $table->index('hospital_feature_id');
        });

        DB::statement("ALTER TABLE hospital_feature_assignments COMMENT = '병원 특징 연결 테이블'");
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_feature_assignments');
    }
};
