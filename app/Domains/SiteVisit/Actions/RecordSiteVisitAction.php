<?php

namespace App\Domains\SiteVisit\Actions;

use Illuminate\Support\Facades\DB;

final class RecordSiteVisitAction
{
    public function execute(string $sessionId): void
    {
        // 재전송·동일 세션의 여러 페이지는 최초 한 번만 집계한다.
        DB::table('site_visit_sessions')->insertOrIgnore(['id' => $sessionId, 'started_at' => now()]);
    }
}
