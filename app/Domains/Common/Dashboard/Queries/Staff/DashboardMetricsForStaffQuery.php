<?php

declare(strict_types=1);

namespace App\Domains\Common\Dashboard\Queries\Staff;

use App\Common\Authorization\AccessPermissions as P;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\HospitalEvent\Models\HospitalEventDB;
use App\Domains\HospitalEvent\Models\HospitalEventRealModelDB;
use App\Domains\RemoteConsultation\Models\RemoteConsultation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class DashboardMetricsForStaffQuery
{
    public function execute(AccountStaff $actor, string $startDate, string $endDate): array
    {
        $start = CarbonImmutable::parse($startDate, 'Asia/Seoul')->startOfDay();
        $end = CarbonImmutable::parse($endDate, 'Asia/Seoul')->addDay()->startOfDay();
        $now = CarbonImmutable::now();
        $from = $start->setTimezone(config('app.timezone'))->toDateTimeString();
        $to = $end->min($now)->setTimezone(config('app.timezone'))->toDateTimeString();
        $dates = [];
        for ($day = $start; $day < $end; $day = $day->addDay()) {
            $dates[] = $day->toDateString();
        }
        $sources = [
            ['events', '이벤트 DB 접수', P::BEAULAB_HOSPITAL_EVENT_DB_SHOW, HospitalEventDB::class, '/customer-db-manage/events'],
            ['remote', '비대면 상담 접수', P::BEAULAB_REMOTE_CONSULTATION_SHOW, RemoteConsultation::class, '/customer-db-manage/consultations'],
            ['models', '리얼모델 접수', P::BEAULAB_HOSPITAL_EVENT_REAL_MODEL_DB_SHOW, HospitalEventRealModelDB::class, '/customer-db-manage/real-models'],
            ['members', '신규 회원', P::BEAULAB_USER_STATISTICS_SHOW, AccountUser::class, '/user-manage/statistics'],
        ];
        $metrics = [];
        foreach ($sources as [$key, $label, $permission, $model, $path]) {
            if (! $actor->can($permission)) {
                continue;
            }
            $query = $model === AccountUser::class ? AccountUser::withTrashed() : $model::query();
            $counts = $query->where('created_at', '>=', $from)->where('created_at', '<', $to)
                ->selectRaw("DATE(CONVERT_TZ(created_at, ?, '+09:00')) AS bucket, COUNT(*) AS total", [$now->format('P')])
                ->groupBy('bucket')->pluck('total', 'bucket');
            $metrics[] = [
                'key' => $key, 'label' => $label, 'total' => (int) $counts->sum(),
                'values' => array_map(fn ($date) => (int) ($counts[$date] ?? 0), $dates),
                'href' => $key === 'members' ? $path : $path.'?'.http_build_query(['start_date' => $startDate, 'end_date' => $endDate]),
            ];
        }

        return [
            'as_of' => $now->toIso8601String(), 'start_date' => $startDate, 'end_date' => $endDate,
            'dates' => $dates, 'metrics' => $metrics,
            'wallet' => $actor->can(P::BEAULAB_HOSPITAL_WALLET_DASHBOARD_SHOW) ? $this->wallet($actor, $from, $to, $startDate, $endDate) : null,
        ];
    }

    private function wallet(AccountStaff $actor, string $from, string $to, string $startDate, string $endDate): array
    {
        $entries = DB::table('hospital_wallet_transaction_entries as e')
            ->join('hospital_wallet_transactions as t', 't.id', '=', 'e.hospital_wallet_transaction_id')
            ->join('hospital_wallet_operations as o', 'o.id', '=', 't.hospital_wallet_operation_id')
            ->where('o.status', 'COMPLETED')->where('t.created_at', '>=', $from)->where('t.created_at', '<', $to)
            ->selectRaw('e.balance_type, o.type, e.direction, SUM(e.amount) AS total')
            ->groupBy('e.balance_type', 'o.type', 'e.direction')->get();
        $sum = fn ($balance, $type, $direction) => (int) $entries->where('balance_type', $balance)->where('type', $type)->where('direction', $direction)->sum('total');
        $rows = [];
        foreach (['PAID' => '유상 포인트', 'SERVICE' => '서비스 포인트'] as $balance => $label) {
            $rows[] = [
                'key' => $balance, 'label' => $label,
                'credit' => $sum($balance, $balance === 'PAID' ? 'CHARGE' : 'SERVICE_GRANT', 'CREDIT'),
                'usage' => $sum($balance, 'USAGE', 'DEBIT'),
                'returned' => $sum($balance, $balance === 'PAID' ? 'REFUND' : 'SERVICE_RECLAIM', 'DEBIT'),
                'adjustment' => $sum($balance, 'REVERSAL', 'CREDIT') - $sum($balance, 'REVERSAL', 'DEBIT'),
            ];
        }

        return [
            'rows' => $rows,
            'href' => $actor->can(P::BEAULAB_HOSPITAL_WALLET_HISTORY_SHOW) ? '/wallet-manage/history?'.http_build_query(['start_date' => $startDate, 'end_date' => $endDate]) : null,
        ];
    }
}
