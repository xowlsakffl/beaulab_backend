<?php

namespace Database\Seeders;

use App\Common\Support\DevelopmentDataGuard;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

final class AccountUserStatisticsDevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        DevelopmentDataGuard::assertEnabled();
        if (! app()->environment(['local', 'testing'])) {
            throw new \LogicException('회원 현황 샘플은 local/testing 환경에서만 생성할 수 있습니다.');
        }

        $now = CarbonImmutable::now('Asia/Seoul');
        $start = $now->startOfMonth()->subMonths(11);
        $inserted = DB::transaction(function () use ($now, $start): int {
            $rows = [];
            $inserted = 0;
            for ($day = $start; $day <= $now; $day = $day->addDay()) {
                $date = $day->toDateString();
                $dailyCount = 80 + (int) sprintf('%u', crc32($date)) % 140 + $day->month * 8;
                if ($day->isWeekend()) {
                    $dailyCount = (int) round($dailyCount * 0.65);
                }
                for ($index = 0; $index < $dailyCount; $index++) {
                    $at = $day->addSeconds((int) floor(($index + 0.5) * 86400 / $dailyCount));
                    if ($at > $now) {
                        break;
                    }
                    // 날짜와 순번으로 ID를 고정해 재실행 시 기존 방문 기록을 보존한다.
                    $rows[] = [
                        'id' => Uuid::uuid5(Uuid::NAMESPACE_URL, "beaulab:demo:site-visit:{$date}:{$index}")->toString(),
                        'started_at' => $at->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                    ];
                    if (count($rows) >= 1000) {
                        $inserted += DB::table('site_visit_sessions')->insertOrIgnore($rows);
                        $rows = [];
                    }
                }
            }
            if ($rows !== []) {
                $inserted += DB::table('site_visit_sessions')->insertOrIgnore($rows);
            }

            return $inserted;
        });

        $this->command?->info("최근 12개월 방문 샘플 {$inserted}건을 추가했습니다. 기존 회원과 방문 기록은 유지했습니다.");
    }
}
