<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Get the migration connection name.
     */
    public function getConnection(): ?string
    {
        return config('telescope.storage.database.connection');
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $schema = Schema::connection($this->getConnection());

        $schema->create('telescope_entries', function (Blueprint $table) {
            $table->bigIncrements('sequence')->comment('Telescope 엔트리 내부 시퀀스 ID');
            $table->uuid('uuid')->comment('Telescope 엔트리 고유 UUID');
            $table->uuid('batch_id')->comment('동일 요청/작업 단위로 묶인 엔트리 배치 ID');
            $table->string('family_hash')->nullable()->comment('연관된 엔트리 그룹 해시');
            $table->boolean('should_display_on_index')
                ->default(true)
                ->comment('Telescope 인덱스 화면 노출 여부');
            $table->string('type', 20)->comment('엔트리 타입(request, query, job 등)');
            $table->longText('content')->comment('엔트리 상세 데이터(JSON 직렬화)');
            $table->dateTime('created_at')->nullable()->comment('엔트리 생성 시각');

            $table->unique('uuid');
            $table->index('batch_id');
            $table->index('family_hash');
            $table->index('created_at');
            $table->index(['type', 'should_display_on_index']);
        });

        DB::statement("ALTER TABLE telescope_entries COMMENT = '[시스템]Laravel Telescope 수집 엔트리 테이블'");

        $schema->create('telescope_entries_tags', function (Blueprint $table) {
            $table->uuid('entry_uuid')->comment('Telescope 엔트리 UUID');
            $table->string('tag')->comment('엔트리에 부여된 태그');

            $table->primary(['entry_uuid', 'tag']);
            $table->index('tag');

            $table->foreign('entry_uuid')
                ->references('uuid')
                ->on('telescope_entries')
                ->onDelete('cascade');
        });

        DB::statement("ALTER TABLE telescope_entries_tags COMMENT = '[시스템]Telescope 엔트리-태그 매핑 테이블'");

        $schema->create('telescope_monitoring', function (Blueprint $table) {
            $table->string('tag')->primary()->comment('Telescope에서 모니터링 중인 태그');
        });

        DB::statement("ALTER TABLE telescope_monitoring COMMENT = '[시스템]Telescope 모니터링 대상 태그 테이블'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $schema = Schema::connection($this->getConnection());

        $schema->dropIfExists('telescope_entries_tags');
        $schema->dropIfExists('telescope_entries');
        $schema->dropIfExists('telescope_monitoring');
    }
};
