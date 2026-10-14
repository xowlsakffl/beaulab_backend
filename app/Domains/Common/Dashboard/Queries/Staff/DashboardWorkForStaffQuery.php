<?php

declare(strict_types=1);

namespace App\Domains\Common\Dashboard\Queries\Staff;

use App\Common\Authorization\AccessPermissions as P;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Chat\Models\ChatMessage;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Models\HospitalStatusChangeRequest;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalEntry\Models\HospitalEntry;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Models\HospitalEventRealModelDB;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use App\Domains\RemoteConsultation\Models\RemoteConsultation;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class DashboardWorkForStaffQuery
{
    public function execute(AccountStaff $actor): array
    {
        return [
            'as_of' => now()->toIso8601String(),
            'reviews' => $this->reviews($actor),
            'reports' => $this->reports($actor),
            'status_requests' => $this->statusRequests($actor),
            'consultations' => $actor->can(P::BEAULAB_CHATBOT_CONSULTATION_SHOW) ? $this->consultations((int) $actor->getKey()) : null,
        ];
    }

    private function reviews(AccountStaff $actor): array
    {
        $sources = [
            ['hospitals', '병의원', P::BEAULAB_HOSPITAL_SHOW, Hospital::class, 'allow_status', 'PENDING', 'name', '/hospital-manage/hospitals'],
            ['doctors', '의료진', P::BEAULAB_DOCTOR_SHOW, HospitalDoctor::class, 'allow_status', 'PENDING', 'name', '/hospital-manage/doctors'],
            ['entries', '입점신청', P::BEAULAB_HOSPITAL_ENTRY_SHOW, HospitalEntry::class, 'allow_status', 'PENDING', 'hospital_name', '/hospital-manage/hospital-entries'],
            ['events', '이벤트', P::BEAULAB_HOSPITAL_EVENT_SHOW, HospitalEvent::class, 'allow_status', 'PENDING', 'name', '/ads-manage/events'],
            ['ads', '광고', P::BEAULAB_HOSPITAL_EVENT_AD_SHOW, HospitalEventAd::class, 'allow_status', 'PENDING', null, '/ads-manage/event-ads'],
            ['remote', '비대면 상담', P::BEAULAB_REMOTE_CONSULTATION_SHOW, RemoteConsultation::class, 'approval_status', 'PENDING', 'name', '/customer-db-manage/consultations'],
            ['models', '리얼모델', P::BEAULAB_HOSPITAL_EVENT_REAL_MODEL_DB_SHOW, HospitalEventRealModelDB::class, 'status', 'RECEIVED', 'name', '/customer-db-manage/real-models'],
        ];
        $groups = [];
        foreach ($sources as [$key, $label, $permission, $model, $column, $pending, $title, $path]) {
            if (! $actor->can($permission)) {
                continue;
            }
            $query = $model::query()->whereIn($column, [$pending, 'REVIEWING']);
            $counts = (clone $query)->selectRaw($column.', COUNT(*) AS total')->groupBy($column)->pluck('total', $column);
            $rows = (clone $query)->oldest('created_at')->orderBy('id')->limit(8)->get(['id', 'created_at', $column, $title ?? 'hospital_event_id']);
            if ($key === 'ads') {
                $rows->load('hospitalEvent:id,name');
            }
            $groups[] = [
                'key' => $key, 'label' => $label, 'pending' => (int) ($counts[$pending] ?? 0),
                'reviewing' => (int) ($counts['REVIEWING'] ?? 0),
                'href' => $path.'?'.http_build_query([$key === 'models' ? 'statuses' : $column => $key === 'models' ? $pending : $pending.',REVIEWING']),
                'rows' => $rows->map(fn ($row) => [
                    'key' => $key.':'.$row->id, 'id' => $row->id, 'label' => $label,
                    'title' => ($title ? $row->{$title} : $row->hospitalEvent?->name) ?: $label.' #'.$row->id,
                    'status' => match ($key) {
                        'models' => HospitalEventRealModelDB::statusLabel($row->{$column}),
                        'remote' => $row->{$column} === 'REVIEWING' ? '검수' : '신청',
                        default => $model::allowStatusLabel($row->{$column}),
                    },
                    'created_at' => $row->created_at?->toIso8601String(), 'href' => $path.'/'.$row->id,
                ])->all(),
            ];
        }

        return $groups;
    }

    private function reports(AccountStaff $actor): array
    {
        $sources = [
            ['surgery', '성형후기', P::BEAULAB_REPORTED_HOSPITAL_REVIEW_SHOW, HospitalReview::class, 'SURGERY', '/reported-post-manage/surgery-reviews', false],
            ['treatment', '쁘띠후기', P::BEAULAB_REPORTED_HOSPITAL_REVIEW_SHOW, HospitalReview::class, 'TREATMENT', '/reported-post-manage/treatment-reviews', false],
            ['surgery_comments', '성형후기 댓글', P::BEAULAB_REPORTED_HOSPITAL_REVIEW_SHOW, HospitalReviewComment::class, 'SURGERY', '/reported-post-manage/surgery-reviews', true],
            ['treatment_comments', '쁘띠후기 댓글', P::BEAULAB_REPORTED_HOSPITAL_REVIEW_SHOW, HospitalReviewComment::class, 'TREATMENT', '/reported-post-manage/treatment-reviews', true],
            ['talks', '토크', P::BEAULAB_REPORTED_TALK_SHOW, Talk::class, null, '/reported-post-manage/talks', false],
            ['talk_comments', '토크 댓글', P::BEAULAB_REPORTED_TALK_SHOW, TalkComment::class, null, '/reported-post-manage/talks', true],
            ['evaluations', '병의원 평가', P::BEAULAB_REPORTED_HOSPITAL_EVALUATION_SHOW, HospitalEvaluation::class, null, '/reported-post-manage/hospital-evaluations', false],
            ['chats', '채팅', P::BEAULAB_REPORTED_CHAT_MESSAGE_SHOW, ChatMessage::class, null, '/reported-post-manage/chats', false],
        ];
        $groups = [];
        foreach ($sources as [$key, $label, $permission, $model, $domain, $path, $comment]) {
            if (! $actor->can($permission)) {
                continue;
            }
            $query = ContentReportState::query()->where('target_type', $model)
                ->whereHasMorph('target', [$model], function (Builder $target) use ($model, $domain): void {
                    if ($model === HospitalReview::class) {
                        $target->where('category_domain', $domain);
                    } elseif ($model === HospitalReviewComment::class) {
                        $target->whereHas('review', fn ($review) => $review->where('category_domain', $domain));
                    } elseif ($model === TalkComment::class) {
                        $target->whereHas('talk');
                    }
                });
            if ($model === ChatMessage::class) {
                $query->where('report_count', '>', 0)->where('warning_status', ContentReportState::WARNING_STATUS_NONE);
            } else {
                $query->whereIn('report_status', [ContentReportState::STATUS_REPORTED, ContentReportState::STATUS_AUTO_BLOCKED]);
            }
            $count = (clone $query)->count();
            $listHref = $path.'?'.http_build_query($comment ? ['board' => 'comments', 'summary_filter' => 'reported_or_auto_blocked'] : ($key === 'chats' ? ['warning_status' => 'NONE'] : ['summary_filter' => 'reported_or_auto_blocked']));
            $textColumn = in_array($model, [HospitalReview::class, Talk::class], true) ? 'title' : ($model === ChatMessage::class ? 'body' : 'content');
            $groups[] = [
                'key' => $key, 'label' => $label, 'pending' => $count, 'reviewing' => 0, 'href' => $listHref,
                'rows' => (clone $query)->with('target:id,'.$textColumn)->oldest('first_reported_at')->orderBy('id')->limit(8)->get()->map(fn ($row) => [
                    'key' => 'report:'.$row->id, 'id' => $row->target_id, 'label' => $label,
                    'title' => Str::limit(strip_tags((string) ($row->target?->{$textColumn} ?: $label.' #'.$row->target_id)), 100),
                    'status' => $row->report_status === ContentReportState::STATUS_AUTO_BLOCKED ? '자동차단' : '신고접수',
                    'created_at' => $row->first_reported_at?->toIso8601String(),
                    'href' => $comment ? $listHref.'&q='.$row->target_id : $path.'/'.$row->target_id,
                ])->all(),
            ];
        }

        return $groups;
    }

    private function statusRequests(AccountStaff $actor): ?array
    {
        if (! $actor->can(P::BEAULAB_HOSPITAL_SHOW) || ! $actor->can(P::BEAULAB_HOSPITAL_STATUS_REQUEST_SHOW)) {
            return null;
        }
        $query = HospitalStatusChangeRequest::query()->where('status', HospitalStatusChangeRequest::STATUS_PENDING)->whereHas('hospital');

        return [
            'key' => 'status_requests', 'label' => '운영상태 변경', 'pending' => (clone $query)->count(), 'reviewing' => 0,
            'href' => '/hospital-manage/hospitals',
            'rows' => (clone $query)->with('hospital:id,name')->oldest('created_at')->orderBy('id')->limit(8)->get()->map(fn ($row) => [
                'key' => 'status:'.$row->id, 'id' => $row->id, 'label' => '운영상태 변경',
                'title' => $row->hospital->name,
                'status' => $row->target_status === Hospital::STATUS_ACTIVE ? '운영재개 요청' : '운영중지 요청',
                'created_at' => $row->created_at?->toIso8601String(), 'href' => '/hospital-manage/hospitals/'.$row->hospital_id,
            ])->all(),
        ];
    }

    private function consultations(int $staffId): array
    {
        $waiting = HospitalChatBotConsultation::query()->where('status', 'WAITING');
        $mine = HospitalChatBotConsultation::query()->where('status', 'CONNECTED')->where('assigned_staff_id', $staffId);
        $unread = (clone $mine)->whereExists(function ($messages) use ($staffId): void {
            $messages->selectRaw('1')->from('hospital_chatbot_consultation_messages as m')
                ->leftJoin('hospital_chatbot_consultation_reads as r', function ($join) use ($staffId): void {
                    $join->on('r.consultation_id', '=', 'm.consultation_id')->where('r.staff_id', $staffId);
                })->whereColumn('m.consultation_id', 'hospital_chatbot_consultations.id')
                ->where('m.sender_type', 'HOSPITAL_USER')->whereRaw('m.sequence > COALESCE(r.last_read_sequence, 0)');
        });
        $oldest = (clone $waiting)->oldest('requested_at')->first(['requested_at']);
        $rows = (clone $waiting)->oldest('requested_at')->orderByDesc('id')->limit(5)->get()
            ->concat((clone $mine)->orderByRaw('COALESCE(last_message_at, connected_at, requested_at) DESC')->orderByDesc('id')->limit(5)->get());
        $rows->load('latestMessage');

        return [
            'waiting' => (clone $waiting)->count(), 'connected_mine' => (clone $mine)->count(), 'unread_mine' => $unread->count(),
            'oldest_requested_at' => $oldest?->requested_at?->toIso8601String(),
            'rows' => $rows->map(fn ($row) => [
                'id' => $row->id, 'hospital_name' => $row->hospital_name_snapshot,
                'status' => $row->status, 'requested_at' => $row->requested_at?->toIso8601String(),
                'message' => Str::limit((string) $row->latestMessage?->content, 100),
                'href' => '/hospital-chatbot/consultations?id='.$row->id,
            ])->all(),
        ];
    }
}
