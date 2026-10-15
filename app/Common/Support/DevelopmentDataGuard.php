<?php

namespace App\Common\Support;

use Illuminate\Support\Facades\DB;
use LogicException;

final class DevelopmentDataGuard
{
    public static function assertEnabled(): void
    {
        if (! config('seeding.development.enabled')) {
            throw new LogicException('개발 데이터 작업은 DEVELOPMENT_SEED_ENABLED=true 설정이 필요합니다.');
        }
        self::assertLocalResources();
        $expected = trim((string) config('seeding.development.database'));
        $actual = (string) DB::connection()->selectOne('SELECT DATABASE() AS name')->name;
        if ($expected === '' || $actual !== $expected) {
            throw new LogicException('DEVELOPMENT_SEED_DATABASE에 작업할 로컬 DB 이름을 명시해 주세요.');
        }
    }

    public static function assertLocalResources(): void
    {
        $connection = DB::connection();
        if (! app()->environment(['local', 'testing'])
            || $connection->getDriverName() !== 'mysql'
            || ! in_array($connection->getConfig('host'), ['127.0.0.1', 'localhost', '::1'], true)) {
            throw new LogicException('개발 데이터 작업은 local/testing 환경의 로컬 MySQL에서만 가능합니다.');
        }
        foreach (['media.disk' => 'public', 'media.private_disk' => 'private_media'] as $key => $fallback) {
            $disk = (string) config($key, $fallback);
            if (config("filesystems.disks.{$disk}.driver") !== 'local') {
                throw new LogicException('개발 데이터의 공개·비공개 미디어 저장소는 모두 local이어야 합니다.');
            }
        }
        if (config('sms.enabled') || config('push.enabled')) {
            throw new LogicException('개발 데이터 작업 전에 SMS_ENABLED와 PUSH_ENABLED를 꺼 주세요.');
        }
        if (! in_array(config('mail.default'), ['log', 'array'], true)) {
            throw new LogicException('개발 데이터 작업에는 MAIL_MAILER=log 또는 array가 필요합니다.');
        }
    }

    public static function assertDemoEmpty(): void
    {
        foreach ([
            'hospital_entries', 'hospitals', 'hospital_doctors', 'hospital_videos',
            'beauties', 'beauty_experts', 'account_hospitals', 'account_beauties',
            'account_users', 'talks', 'hospital_reviews', 'hospital_evaluations',
            'hospital_events', 'hospital_wallets', 'remote_consultations',
            'hospital_event_dbs', 'hospital_event_real_model_dbs', 'hospital_event_ads',
            'hospital_wallet_operations', 'hospital_status_change_requests',
            'notices', 'hospital_promotions', 'harmful_words',
            'banner_exposures', 'event_exposures', 'review_exposures', 'video_exposures',
            'hospital_chatbot_qnas', 'hospital_chatbot_consultations', 'hospital_chatbot_events',
            'chats', 'site_visit_sessions', 'notification_inboxes',
            'admin_notes', 'operation_histories', 'media',
        ] as $table) {
            if (DB::table($table)->exists()) {
                throw new LogicException("전체 데모는 빈 업무 DB에서만 생성합니다({$table}). 기존 데이터는 변경하지 않았습니다.");
            }
        }
    }

    public static function requireCategories(string $domain): void
    {
        if (! DB::table('categories')->where('domain', $domain)->where('status', 'ACTIVE')->exists()) {
            throw new LogicException("활성 기본 카테고리가 필요합니다({$domain}). CategorySeeder를 먼저 실행해 주세요.");
        }
    }
}
