<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_inboxes', function (Blueprint $table) {
            $table->id()->comment('알림 ID');

            $table->string('recipient_type', 32)->comment('수신 대상 유형');
            $table->unsignedBigInteger('recipient_id')->comment('수신 대상 ID');

            $table->string('actor_type', 32)->nullable()->comment('알림 발생 주체 유형');
            $table->unsignedBigInteger('actor_id')->nullable()->comment('알림 발생 주체 ID');

            $table->string('event_type', 100)->comment('도메인 이벤트 타입');
            $table->string('title', 255)->nullable()->comment('알림 제목');
            $table->text('body')->nullable()->comment('알림 본문');

            $table->string('aggregation_key', 191)->nullable()->comment('같은 알림을 묶는 집계 키');
            $table->string('open_aggregation_key', 191)->nullable()->comment('미읽음 집계 유니크 잠금 키');
            $table->unsignedInteger('event_count')->default(1)->comment('집계된 이벤트 수');

            $table->string('target_type', 100)->nullable()->comment('알림 대상 엔티티 유형');
            $table->unsignedBigInteger('target_id')->nullable()->comment('알림 대상 엔티티 ID');

            $table->json('payload')->nullable()->comment('알림 payload');

            $table->timestamp('read_at')->nullable()->comment('읽음 시각');

            $table->timestamps();

            $table->index(['recipient_type', 'recipient_id', 'read_at', 'created_at'], 'notification_inboxes_recipient_idx');
            $table->index(['event_type', 'created_at'], 'notification_inboxes_event_created_idx');
            $table->index(['target_type', 'target_id'], 'notification_inboxes_target_idx');
            $table->unique(
                ['recipient_type', 'recipient_id', 'open_aggregation_key'],
                'notification_inboxes_recipient_open_agg_unique'
            );
            $table->index(
                ['recipient_type', 'recipient_id', 'aggregation_key', 'read_at'],
                'notification_inboxes_recipient_aggregation_idx'
            );
        });

        DB::statement("ALTER TABLE notification_inboxes COMMENT = '도메인 공통 인앱 알림함'");

        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id()->comment('알림 발송 이력 ID');

            $table->foreignId('notification_inbox_id')
                ->comment('알림함 ID')
                ->constrained('notification_inboxes')
                ->cascadeOnDelete();

            $table->string('channel', 20)->comment('발송 채널(IN_APP, PUSH, EMAIL, WEB)');
            $table->string('status', 20)->default('PENDING')->comment('발송 상태(PENDING, SENT, FAILED)');
            $table->string('provider', 50)->nullable()->comment('발송 제공자(FCM, APNS, MAIL, REVERB)');
            $table->string('provider_message_id', 191)->nullable()->comment('외부 제공자 메시지 ID');
            $table->timestamp('attempted_at')->nullable()->comment('발송 시도 시각');
            $table->timestamp('delivered_at')->nullable()->comment('발송 완료 시각');
            $table->timestamp('failed_at')->nullable()->comment('발송 실패 시각');
            $table->text('error_message')->nullable()->comment('마지막 실패 사유');

            $table->timestamps();

            $table->index(['channel', 'status', 'attempted_at'], 'notification_deliveries_channel_status_idx');
            $table->index(['notification_inbox_id', 'channel'], 'notification_deliveries_inbox_channel_idx');
        });

        DB::statement("ALTER TABLE notification_deliveries COMMENT = '채널별 알림 발송 이력'");

        Schema::create('notification_devices', function (Blueprint $table) {
            $table->id()->comment('알림 디바이스 ID');

            $table->string('owner_type', 32)->comment('소유 대상 유형');
            $table->unsignedBigInteger('owner_id')->comment('소유 대상 ID');

            $table->string('platform', 20)->comment('플랫폼(IOS, ANDROID, WEB)');
            $table->string('device_uuid', 100)->nullable()->comment('클라이언트 디바이스 식별자');
            $table->string('push_token', 255)->comment('푸시 또는 브라우저 토큰');
            $table->string('app_version', 32)->nullable()->comment('앱 버전');
            $table->timestamp('last_seen_at')->nullable()->comment('마지막 활성 시각');
            $table->timestamp('revoked_at')->nullable()->comment('토큰 폐기 시각');
            $table->json('metadata')->nullable()->comment('디바이스 메타데이터');

            $table->timestamps();

            $table->unique('push_token', 'notification_devices_push_token_unique');
            $table->index(['owner_type', 'owner_id', 'revoked_at'], 'notification_devices_owner_active_idx');
            $table->index(['platform', 'last_seen_at'], 'notification_devices_platform_seen_idx');
        });

        DB::statement("ALTER TABLE notification_devices COMMENT = '푸시 디바이스 및 브라우저 엔드포인트'");

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id()->comment('알림 수신 설정 ID');

            $table->string('owner_type', 32)->comment('소유 대상 유형');
            $table->unsignedBigInteger('owner_id')->comment('소유 대상 ID');

            $table->string('event_type', 100)->comment('알림 이벤트 타입');
            $table->boolean('in_app')->default(true)->comment('인앱 수신 여부');
            $table->boolean('push')->default(true)->comment('푸시 수신 여부');
            $table->boolean('email')->default(false)->comment('이메일 수신 여부');
            $table->json('metadata')->nullable()->comment('설정 메타데이터');

            $table->timestamps();

            $table->unique(['owner_type', 'owner_id', 'event_type'], 'notification_preferences_owner_event_unique');
            $table->index(['event_type', 'updated_at'], 'notification_preferences_event_updated_idx');
        });

        DB::statement("ALTER TABLE notification_preferences COMMENT = '이벤트별 알림 채널 설정'");
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notification_devices');
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('notification_inboxes');
    }
};
