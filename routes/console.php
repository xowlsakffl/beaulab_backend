<?php

use App\Domains\Common\Notification\Jobs\SendPushNotificationDeliveryJob;
use App\Domains\Common\Notification\Models\NotificationDelivery;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use App\Domains\Notice\Actions\Common\CleanupTempEditorImagesAction;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// 개발용 기본 예시 커맨드 (Laravel 기본 제공)
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 공지 에디터 임시 이미지 정리 커맨드 (작성 취소/이탈 시 남은 파일 정리)
Artisan::command('notice:cleanup-temp-editor-images {--hours=24}', function () {
    $hours = max(1, (int) $this->option('hours'));
    $deleted = app(CleanupTempEditorImagesAction::class)->execute($hours);

    $this->info("Deleted temp editor images: {$deleted}");
})->purpose('Delete stale temporary notice editor images');

// 장애/재배포로 dispatch를 놓친 PUSH delivery를 재큐잉한다.
Artisan::command('notifications:send-pending-push {--limit=100}', function () {
    $limit = max(1, min((int) $this->option('limit'), 1000));

    $ids = NotificationDelivery::query()
        ->where('channel', NotificationDelivery::CHANNEL_PUSH)
        ->where('status', NotificationDelivery::STATUS_PENDING)
        ->orderBy('id')
        ->limit($limit)
        ->pluck('id');

    foreach ($ids as $id) {
        SendPushNotificationDeliveryJob::dispatch((int) $id);
    }

    $this->info("Queued pending push deliveries: {$ids->count()}");
})->purpose('Queue pending push notification deliveries');

// 병원별 병의원 평가 수/평균 평점 집계 보정
Artisan::command('hospital-evaluations:refresh-hospital-ratings {--hospital-id=*}', function () {
    $hospitalIds = collect($this->option('hospital-id') ?? [])
        ->map(static fn ($hospitalId): int => (int) $hospitalId)
        ->filter(static fn (int $hospitalId): bool => $hospitalId > 0)
        ->unique()
        ->values();

    if ($hospitalIds->isNotEmpty()) {
        HospitalEvaluation::refreshStoredAverageRatings($hospitalIds->all());
        HospitalEvaluation::refreshHospitalRatingAggregates($hospitalIds->all());
        $this->info("Refreshed hospital evaluation ratings: {$hospitalIds->count()} hospitals");

        return;
    }

    $refreshedCount = 0;

    Hospital::query()
        ->select(['id'])
        ->orderBy('id')
        ->chunkById(500, function ($hospitals) use (&$refreshedCount): void {
            $ids = $hospitals
                ->pluck('id')
                ->map(static fn ($hospitalId): int => (int) $hospitalId)
                ->values()
                ->all();

            HospitalEvaluation::refreshStoredAverageRatings($ids);
            HospitalEvaluation::refreshHospitalRatingAggregates($ids);
            $refreshedCount += count($ids);
        });

    $this->info("Refreshed hospital evaluation ratings: {$refreshedCount} hospitals");
})->purpose('Refresh denormalized hospital evaluation rating aggregates');

// Schedule Monitor 대상 작업 동기화 (모니터링 대상/설정 갱신)
Schedule::command('schedule-monitor:sync')->dailyAt('02:50');

// 공지 에디터 임시 이미지 주기 정리 (스토리지 누수 방지)
Schedule::command('notice:cleanup-temp-editor-images --hours=24')->hourly();

// Horizon 메트릭 스냅샷 수집 (대시보드 그래프 데이터 유지)
Schedule::command('horizon:snapshot')->everyFiveMinutes();

// 오래된 큐 배치 메타 정리 (job_batches 비대화 방지)
Schedule::command('queue:prune-batches --hours=72 --unfinished=72 --cancelled=168')->dailyAt('03:10');

// 오래된 실패 작업 기록 정리 (failed_jobs 비대화 방지)
Schedule::command('queue:prune-failed --hours=168')->dailyAt('03:20');

// 병원별 평가 평점 집계 정합성 보정
Schedule::command('hospital-evaluations:refresh-hospital-ratings')->dailyAt('03:30');
