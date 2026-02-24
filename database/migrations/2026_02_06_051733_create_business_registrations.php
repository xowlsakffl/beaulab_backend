<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_registrations', function (Blueprint $table) {
            $table->id()->comment('사업자 등록증 고유 ID');

            // Polymorphic owner (hospital, beauty 등)
            $table->string('owner_type', 50)->comment('소유 엔티티 타입(hospital, beauty 등)');
            $table->unsignedBigInteger('owner_id')->comment('소유 엔티티 고유 ID');

            $table->string('business_number', 20)->unique()->comment('사업자 등록번호');
            $table->string('company_name', 255)->comment('상호명');
            $table->string('ceo_name', 100)->comment('대표자명');

            $table->string('business_type', 100)->nullable()->comment('업태');
            $table->string('business_item', 100)->nullable()->comment('종목');

            $table->string('business_address', 255)->nullable()->comment('사업장 주소');
            $table->string('business_address_detail', 255)->nullable()->comment('사업장 상세 주소');

            $table->date('issued_at')->nullable()->comment('사업자 등록일');

            $table->string('status', 20)->default('ACTIVE')->comment('등록증 상태(ACTIVE, EXPIRED, REVOKED)');

            $table->timestamps();

            // indexes
            $table->index(['owner_type', 'owner_id'], 'business_registrations_owner_index');
            $table->index('status');

        });

        DB::statement("ALTER TABLE business_registrations COMMENT = '사업자 등록 테이블'");
    }

    public function down(): void
    {
        Schema::dropIfExists('business_registrations');
    }
};
