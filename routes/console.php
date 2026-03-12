<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('notice:cleanup-temp-editor-images {--hours=24}', function () {
    $hours = max(1, (int) $this->option('hours'));
    $cutoff = now()->subHours($hours)->getTimestamp();
    $disk = Storage::disk('public');
    $dir = 'notice/editor-images/temp';

    if (! $disk->exists($dir)) {
        $this->info('No temp editor image directory exists.');

        return;
    }

    $files = $disk->allFiles($dir);
    $deleted = 0;

    foreach ($files as $file) {
        try {
            $lastModified = $disk->lastModified($file);
        } catch (\Throwable) {
            continue;
        }

        if ($lastModified > $cutoff) {
            continue;
        }

        $disk->delete($file);
        $deleted++;
    }

    $this->info("Deleted temp editor images: {$deleted}");
})->purpose('Delete stale temporary notice editor images');

Schedule::command('schedule-monitor:sync')->dailyAt('02:50');
Schedule::command('notice:cleanup-temp-editor-images --hours=24')->hourly();
Schedule::command('horizon:snapshot')->everyFiveMinutes();
Schedule::command('queue:prune-batches --hours=72 --unfinished=72 --cancelled=168')->dailyAt('03:10');
Schedule::command('queue:prune-failed --hours=168')->dailyAt('03:20');
