<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beauty_business_registrations', function (Blueprint $table) {
            $table->id()->comment('사업자 등록증 고유 ID');

            $table->foreignId('beauty_id')->comment('소속 뷰티 ID')->constrained('beauties')->cascadeOnDelete();

            $table->string('business_number', 20)->unique()->comment('사업자 등록번호');
            $table->string('company_name', 255)->comment('상호명');
            $table->string('ceo_name', 100)->comment('대표자명');

            $table->string('business_type', 100)->nullable()->comment('업태');
            $table->string('business_item', 100)->nullable()->comment('종목');

            $table->string('business_address', 255)->nullable()->comment('사업장 주소');
            $table->string('business_address_detail', 255)->nullable()->comment('사업장 상세 주소');

            $table->date('issued_at')->nullable()->comment('사업자 등록일');

            $table->string('status', 20)->default('ACTIVE')->comment('운영 상태(ACTIVE, EXPIRED, REVOKED)');

            $table->timestamps();

            // indexes
            $table->index('beauty_id');
            $table->index('status');

        });

        DB::statement("ALTER TABLE beauty_business_registrations COMMENT = '뷰티 사업자 등록 테이블'");
    }

    public function down(): void
    {
        Schema::dropIfExists('beauty_business_registrations');
    }
};
