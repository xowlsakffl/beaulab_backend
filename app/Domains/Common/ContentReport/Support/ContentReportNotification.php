<?php

namespace App\Domains\Common\ContentReport\Support;

use App\Common\Authorization\AccessPermissions as P;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\Common\Notification\Actions\StaffNotificationPublisher;
use Illuminate\Database\Eloquent\Builder;

final class ContentReportNotification
{
    public static function definitions(): array
    {
        $sources = [
            ['hospital_review', '성형후기', 'surgery-reviews', 'SURGERY', P::BEAULAB_REPORTED_HOSPITAL_REVIEW_SHOW, P::BEAULAB_REPORTED_HOSPITAL_REVIEW_STATUS_UPDATE],
            ['hospital_review', '쁘띠후기', 'treatment-reviews', 'TREATMENT', P::BEAULAB_REPORTED_HOSPITAL_REVIEW_SHOW, P::BEAULAB_REPORTED_HOSPITAL_REVIEW_STATUS_UPDATE],
            ['hospital_review_comment', '성형후기 댓글', 'surgery-reviews', 'SURGERY', P::BEAULAB_REPORTED_HOSPITAL_REVIEW_SHOW, P::BEAULAB_REPORTED_HOSPITAL_REVIEW_STATUS_UPDATE],
            ['hospital_review_comment', '쁘띠후기 댓글', 'treatment-reviews', 'TREATMENT', P::BEAULAB_REPORTED_HOSPITAL_REVIEW_SHOW, P::BEAULAB_REPORTED_HOSPITAL_REVIEW_STATUS_UPDATE],
            ['talk', '토크', 'talks', null, P::BEAULAB_REPORTED_TALK_SHOW, P::BEAULAB_REPORTED_TALK_STATUS_UPDATE],
            ['talk_comment', '토크 댓글', 'talks', null, P::BEAULAB_REPORTED_TALK_SHOW, P::BEAULAB_REPORTED_TALK_STATUS_UPDATE],
            ['hospital_evaluation', '병의원 평가', 'hospital-evaluations', null, P::BEAULAB_REPORTED_HOSPITAL_EVALUATION_SHOW, P::BEAULAB_REPORTED_HOSPITAL_EVALUATION_STATUS_UPDATE],
            ['chat_message', '채팅', 'chats', null, P::BEAULAB_REPORTED_CHAT_MESSAGE_SHOW, P::BEAULAB_REPORTED_CHAT_MESSAGE_STATUS_UPDATE],
            ['hospital_video', '동영상', null, null, P::BEAULAB_REPORTED_VIDEO_SHOW, P::BEAULAB_REPORTED_VIDEO_STATUS_UPDATE],
        ];
        $definitions = [];
        foreach ($sources as [$alias, $title, $path, $domain, $show, $process]) {
            $key = 'report_'.$alias.($domain ? '_'.strtolower($domain) : '');
            $menu = $path ? '/reported-post-manage/'.$path : '/video-manage/videos';
            $comment = str_ends_with($alias, '_comment');
            $definitions['staff.'.$key] = [
                'title' => $title.' 신고 접수', 'target_type' => $key, 'model' => ContentReportState::class,
                'route' => $menu.($comment ? '?board=comments&q=%d' : '/%d'), 'menu' => $menu,
                'status' => $alias === 'chat_message' ? 'warning_status' : 'report_status',
                'pending' => $alias === 'chat_message' ? ['NONE'] : ['REPORTED', 'AUTO_BLOCKED'],
                'permissions' => [$show, $process, ...($alias === 'hospital_video' ? [P::BEAULAB_VIDEO_SHOW] : [])], 'report_alias' => $alias, 'report_domain' => $domain,
            ];
        }

        return $definitions;
    }

    public static function targets(array $definition): Builder
    {
        $alias = $definition['report_alias'];
        $model = ContentReportTargetRegistry::classForAlias($alias);

        return ContentReportState::query()->where('target_type', $model)->where('report_count', '>', 0)
            ->whereHasMorph('target', [$model], function (Builder $target) use ($alias, $definition): void {
                if ($alias === 'hospital_review') {
                    $target->where('category_domain', $definition['report_domain']);
                } elseif ($alias === 'hospital_review_comment') {
                    $target->whereHas('review', fn ($review) => $review->where('category_domain', $definition['report_domain']));
                } elseif ($alias === 'talk_comment') {
                    $target->whereHas('talk');
                }
            });
    }

    public function requested(ContentReportState $state, int $reportId): void
    {
        foreach (self::definitions() as $type => $definition) {
            if ($state->target_type !== ContentReportTargetRegistry::classForAlias($definition['report_alias'])
                || ! in_array($state->getAttribute($definition['status']), $definition['pending'], true)
                || ! self::targets($definition)->whereKey($state->id)->exists()) {
                continue;
            }
            app(StaffNotificationPublisher::class)->record($type, 'content-report:'.$reportId, $definition['target_type'], (int) $state->id);
        }
    }

    public function changed(ContentReportState $state): void
    {
        foreach (self::definitions() as $definition) {
            if ($state->target_type === ContentReportTargetRegistry::classForAlias($definition['report_alias'])) {
                app(StaffNotificationPublisher::class)->invalidateTarget($definition['target_type'], (int) $state->id);
            }
        }
    }
}
