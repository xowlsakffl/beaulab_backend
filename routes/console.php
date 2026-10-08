<?php

use App\Domains\Common\Media\Models\Media;
use App\Domains\Common\Media\Services\MediaVariantGenerator;
use App\Domains\Common\Notification\Jobs\SendPushNotificationDeliveryJob;
use App\Domains\Common\Notification\Models\NotificationDelivery;
use App\Domains\Common\Sms\Actions\SmsPendingDispatchAction;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use App\Domains\HospitalWallet\Queries\HospitalWalletIntegrityAuditQuery;
use App\Domains\Notice\Actions\Common\CleanupTempEditorImagesAction;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Symfony\Component\Console\Command\Command;

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
        ->where(fn ($query) => $query->whereNull('processing_until')->orWhere('processing_until', '<=', now()))
        ->where(fn ($query) => $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()))
        ->orderBy('id')
        ->limit($limit)
        ->pluck('id');

    foreach ($ids as $id) {
        SendPushNotificationDeliveryJob::dispatch((int) $id);
    }

    $this->info("Queued pending push deliveries: {$ids->count()}");
})->purpose('Queue pending push notification deliveries');

// Redis 장애 등으로 큐 등록이 누락되거나 장기 대기 중인 문자를 재큐잉한다.
Artisan::command('chat:dispatch-pending-broadcasts {--limit=500}', function () {
    $ids = \App\Domains\Chat\Models\ChatMessage::query()->where('broadcast_pending', true)
        ->orderBy('id')->limit(max(1, min((int) $this->option('limit'), 1000)))->pluck('id');
    foreach ($ids as $id) {
        \App\Domains\Chat\Jobs\BroadcastChatMessageJob::dispatch((int) $id);
    }
    $this->info('Queued pending chat broadcasts: '.$ids->count());
})->purpose('Recover committed chat messages whose broadcast was not completed');

Artisan::command('sms:dispatch-pending {--limit=100} {--stale-minutes=}', function () {
    $limit = max(1, min((int) $this->option('limit'), 1000));
    $staleMinutes = $this->option('stale-minutes');
    $staleMinutes = is_numeric($staleMinutes) ? max(1, (int) $staleMinutes) : null;
    app(\App\Domains\Common\Sms\Actions\SmsDeliveryRecoveryAction::class)->execute($limit);
    $queuedCount = app(SmsPendingDispatchAction::class)->execute(
        limit: $limit,
        staleMinutes: $staleMinutes,
    );

    $this->info("Queued pending SMS deliveries: {$queuedCount}");
})->purpose('Queue pending SMS deliveries');

// 기존 업로드 이미지에 thumb/medium variant를 생성한다.
Artisan::command('media:cleanup-files {--limit=500}', function () {
    $limit = max(1, min((int) $this->option('limit'), 1000));
    $files = app(\App\Domains\Common\Media\Services\MediaFileLifecycle::class);
    $this->info('Expired promotion editor uploads: '.app(\App\Domains\HospitalPromotion\Actions\Common\HospitalPromotionEditorImagesAction::class)->pruneExpired($limit));
    $this->info('Deleted file tasks: '.$files->purge($limit));
    $this->info('Pruned staging manifests: '.$files->pruneStaging($limit));
})->purpose('Delete committed media removals and stale unreferenced uploads');

Artisan::command('media:privatize {--apply} {--limit=500}', function () {
    $apply = (bool) $this->option('apply');
    $limit = max(1, min((int) $this->option('limit'), 1000));
    $count = 0;
    $query = Media::query()->where('disk', '!=', (string) config('media.private_disk', 'private_media'))
        ->where(function ($query): void {
            foreach (config('media.private_collections', []) as $owner => $collections) {
                $query->orWhere(fn ($query) => $query->where('model_type', $owner)->whereIn('collection', $collections));
            }
        })->orderBy('id')->limit($limit);
    foreach ($query->get() as $media) {
        $this->line(($apply ? 'Migrate ' : 'Would migrate ').'media ID '.$media->id);
        if ($apply) {
            app(\App\Domains\Common\Media\Actions\PrivatizeMediaAction::class)->execute((int) $media->id);
        }
        $count++;
    }
    $this->info(($apply ? 'Processed: ' : 'Dry run candidates: ').$count);
})->purpose('Copy sensitive media to private storage; --apply enables changes');

Artisan::command('media:generate-variants {--force} {--limit=500}', function () {
    $force = (bool) $this->option('force');
    $limit = max(1, min((int) $this->option('limit'), 1000));
    $generator = app(MediaVariantGenerator::class);
    $processedCount = 0;
    $updatedCount = 0;
    $skippedCount = 0;

    Media::query()
        ->select(['id', 'disk', 'path', 'mime_type', 'metadata'])
        ->where('mime_type', 'like', 'image/%')
        ->orderBy('id')
        ->chunkById($limit, function ($mediaItems) use (
            $force,
            $generator,
            &$processedCount,
            &$updatedCount,
            &$skippedCount,
        ): void {
            foreach ($mediaItems as $media) {
                $processedCount++;

                $metadata = is_array($media->metadata) ? $media->metadata : [];
                $existingVariants = $metadata['variants'] ?? null;

                if (! $force && is_array($existingVariants) && $existingVariants !== []) {
                    $skippedCount++;

                    continue;
                }

                $variants = $generator->generate(
                    disk: (string) $media->disk,
                    path: (string) $media->path,
                    mimeType: $media->mime_type,
                );

                if ($variants === []) {
                    $skippedCount++;

                    continue;
                }

                $metadata['variants'] = $variants;
                $media->forceFill(['metadata' => $metadata])->save();
                $updatedCount++;
            }
        });

    $this->info("Processed media: {$processedCount}");
    $this->info("Updated variants: {$updatedCount}");
    $this->info("Skipped media: {$skippedCount}");
})->purpose('Generate thumb and medium variants for existing image media');

// 병의원별 병의원 평가 수/평균 평점 집계 보정
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

// 저장은 됐는데 발행을 놓친 상담 메시지를 다시 집어 간다.
Artisan::command('hospital-chatbot:dispatch-pending-broadcasts {--limit=500}', function () {
    $limit = max(1, min((int) $this->option('limit'), 1000));

    /*
     * 발행이 꺼져 있으면 아무것도 집지 않는다.
     *
     * log·null 드라이버에서는 Job이 돌아도 나가는 데가 없다. 게다가 이 커맨드는
     * 매분 돈다. 큐 워커가 없으면 같은 메시지를 매분 다시 밀어 넣어 Redis만 부푼다.
     * 실시간을 폴링으로 두기로 한 이상 여기서 멈추는 게 맞다.
     */
    if (in_array((string) config('broadcasting.default'), ['log', 'null'], true)) {
        $this->info('Broadcasting is disabled; skipping pending chatbot broadcasts.');

        return;
    }

    $ids = \App\Domains\HospitalChatBot\Models\HospitalChatBotConsultationMessage::query()
        ->where('broadcast_pending', true)
        ->orderBy('id')
        ->limit($limit)
        ->pluck('id');

    foreach ($ids as $id) {
        \App\Domains\HospitalChatBot\Jobs\BroadcastHospitalChatBotMessageJob::dispatch((int) $id);
    }

    // 재시도는 조용히 넘어가면 안 된다. 매분 같은 수가 잡히면 발행이 아예 안 되고 있다는 뜻이다.
    if ($ids->isNotEmpty()) {
        \Illuminate\Support\Facades\Log::warning('챗봇 메시지 발행 재시도', [
            'event' => \App\Domains\HospitalChatBot\Support\HospitalChatBotLog::BROADCAST_RETRIED,
            'queued' => $ids->count(),
            'limit' => $limit,
        ]);
    }

    $this->info('Queued pending hospital chatbot broadcasts: '.$ids->count());
})->purpose('Recover committed chatbot messages whose broadcast was not completed');

// 지금 상태를 지표로 남긴다(PRD 16.6). 수집기에서 event로 묶어 그래프로 쓴다.
Artisan::command('hospital-chatbot:report-metrics', function () {
    $metrics = app(\App\Domains\HospitalChatBot\Actions\Common\HospitalChatBotMetricsAction::class)->execute();

    foreach ($metrics as $key => $value) {
        $this->line(sprintf('%-34s %s', $key, $value ?? '-'));
    }
})->purpose('Log current hospital chatbot gauges for the metrics pipeline');

// 상담원이 아무도 잡지 않은 대기 상담을 자동으로 취소한다(PRD D-07).
Artisan::command('hospital-chatbot:expire-waiting-consultations {--limit=500}', function () {
    $minutes = max(1, (int) config('hospital_chatbot.waiting_timeout_minutes', 30));
    $limit = max(1, min((int) $this->option('limit'), 1000));
    $expired = app(\App\Domains\HospitalChatBot\Actions\Common\HospitalChatBotConsultationExpireWaitingAction::class)
        ->execute($minutes, $limit);

    $this->info("Expired waiting consultations: {$expired}");
})->purpose('Cancel chatbot consultations left waiting past the configured timeout');

Artisan::command('hospital-wallet:audit-integrity', function () {
    $counts = app(HospitalWalletIntegrityAuditQuery::class)->counts();
    $violations = collect($counts)->filter(static fn (int $count): bool => $count > 0);

    $this->table(
        ['check', 'violations'],
        collect($counts)->map(static fn (int $count, string $check): array => [$check, $count])->values()->all(),
    );

    if ($violations->isNotEmpty()) {
        logger()->critical('Hospital wallet integrity violations detected.', $violations->all());

        return Command::FAILURE;
    }

    return Command::SUCCESS;
})->purpose('Audit hospital wallet balance and ledger invariants');

// Schedule Monitor 대상 작업 동기화 (모니터링 대상/설정 갱신)
Schedule::command('schedule-monitor:sync')->dailyAt('02:50');

// 공지 에디터 임시 이미지 주기 정리 (스토리지 누수 방지)
Schedule::command('notice:cleanup-temp-editor-images --hours=24')->hourly();

// Horizon 메트릭 스냅샷 수집 (대시보드 그래프 데이터 유지)
Schedule::command('horizon:snapshot')->everyFiveMinutes();

Schedule::command('media:cleanup-files')->everyFiveMinutes()->withoutOverlapping();

Schedule::command('notifications:send-pending-push --limit=500')->everyMinute()->withoutOverlapping();
Schedule::command('staff-notifications:dispatch-pending --limit=500')->everyMinute()->withoutOverlapping();
Schedule::command('chat:dispatch-pending-broadcasts')->everyMinute()->withoutOverlapping();

// 문자 원장 생성 후 Redis 큐 등록 자체가 누락된 건만 자동 복구한다.
Schedule::command('sms:dispatch-pending --limit=500 --stale-minutes=5')
    ->everyMinute()
    ->withoutOverlapping();

// 오래된 큐 배치 메타 정리 (job_batches 비대화 방지)
Schedule::command('queue:prune-batches --hours=72 --unfinished=72 --cancelled=168')->dailyAt('03:10');

// 오래된 실패 작업 기록 정리 (failed_jobs 비대화 방지)
Schedule::command('queue:prune-failed --hours=168')->dailyAt('03:20');

// 병의원별 평가 평점 집계 정합성 보정
Schedule::command('hospital-evaluations:refresh-hospital-ratings')->dailyAt('03:30');

Schedule::command('hospital-chatbot:dispatch-pending-broadcasts')->everyMinute()->withoutOverlapping();

// 지표는 5분마다 남긴다. 매분은 대부분 0인 줄만 쌓이고, 대기 상담은 어차피 30분에
// 자동 만료되므로 5분 간격이면 놓치지 않는다.
Schedule::command('hospital-chatbot:report-metrics')->everyFiveMinutes()->withoutOverlapping();

// 대기 상담 자동 만료 (기본 30분)
Schedule::command('hospital-chatbot:expire-waiting-consultations')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('hospital-wallet:audit-integrity')
    ->dailyAt('03:40')
    ->withoutOverlapping();
