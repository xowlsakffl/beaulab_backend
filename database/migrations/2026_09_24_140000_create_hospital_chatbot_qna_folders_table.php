<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_chatbot_qna_folders', function (Blueprint $table) {
            $table->id()->comment('Q&A 폴더 ID');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('상위 폴더 ID. 없으면 대분류');
            // 2단 고정(대분류 > 소분류 > 질문). 깊이를 컬럼으로 들고 있어야
            // 트리를 타지 않고도 제약을 검사할 수 있다.
            $table->unsignedTinyInteger('depth')->comment('1=대분류, 2=소분류');
            $table->string('name', 100)->comment('폴더 이름');
            $table->unsignedInteger('sort_order')->default(0)->comment('같은 상위 안에서의 노출 순서');
            $table->foreignId('created_by_staff_id')->nullable()->comment('등록한 직원 계정 ID');
            $table->foreignId('updated_by_staff_id')->nullable()->comment('수정한 직원 계정 ID');
            $table->timestamps();

            // parent_key가 parent_id를 참조하는 생성 컬럼이라 MySQL이 CASCADE를 막는다.
            // 어차피 비어 있을 때만 삭제하므로 RESTRICT가 의도에도 맞다.
            $table->foreign('parent_id', 'hcqf_parent_fk')
                ->references('id')->on('hospital_chatbot_qna_folders')->restrictOnDelete();
            $table->foreign('created_by_staff_id', 'hcqf_created_by_fk')
                ->references('id')->on('account_staffs')->nullOnDelete();
            $table->foreign('updated_by_staff_id', 'hcqf_updated_by_fk')
                ->references('id')->on('account_staffs')->nullOnDelete();

            // 같은 상위 아래에서 이름이 겹치면 고르는 사람이 구분할 수 없다.
            // parent_id가 NULL이면 MySQL 유니크가 걸리지 않으므로 0으로 접어서 비교한다.
            $table->unsignedBigInteger('parent_key')
                ->storedAs('IFNULL(parent_id, 0)')
                ->comment('유니크 비교용. 최상위는 0');
            $table->unique(['parent_key', 'name'], 'hcqf_parent_name_unique');
            $table->index(['depth', 'sort_order', 'id'], 'hcqf_depth_sort_index');
        });

        DB::statement("ALTER TABLE hospital_chatbot_qna_folders COMMENT = '병원 챗봇 Q&A 폴더(2단)'");
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_chatbot_qna_folders');
    }
};
