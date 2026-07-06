<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Actions\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Models\HospitalEventRealModelDB;
use App\Domains\HospitalEvent\Queries\User\HospitalEventRealModelDBCreateForUserQuery;
use Illuminate\Support\Facades\DB;

final class HospitalEventRealModelDBCreateForUserAction
{
    public function __construct(
        private readonly HospitalEventRealModelDBCreateForUserQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
    ) {}

    public function execute(AccountUser $user, HospitalEvent $event, array $payload): array
    {
        $this->assertEventCanBeApplied($event);

        DB::transaction(function () use ($user, $event, $payload): void {
            $application = $this->query->create([
                'account_user_id' => (int) $user->id,
                'hospital_id' => (int) $event->hospital_id,
                'hospital_event_id' => (int) $event->id,
                'name' => $payload['name'],
                'gender' => $payload['gender'],
                'birth_date' => $payload['birth_date'],
                'phone' => $payload['phone'],
                'height_cm' => (int) $payload['height_cm'],
                'weight_kg' => (int) $payload['weight_kg'],
                'surgery_period' => $payload['surgery_period'],
                'support_part' => $payload['support_part'],
                'instagram_url' => $payload['instagram_url'] ?? null,
                'blog_url' => $payload['blog_url'] ?? null,
                'special_notes' => $payload['special_notes'] ?? [],
                'application_reason' => $payload['application_reason'],
                'inquiry' => $payload['inquiry'] ?? null,
                'status' => HospitalEventRealModelDB::STATUS_RECEIVED,
                'author_ip' => $payload['author_ip'] ?? null,
                'user_agent' => $payload['user_agent'] ?? null,
            ]);

            $this->mediaAttachAction->attachMany(
                $application,
                $payload['images'] ?? [],
                HospitalEventRealModelDB::COLLECTION_IMAGES,
                'hospital-event-real-model-db',
                'images',
                true,
            );
        });

        return [
            'message' => '리얼모델 신청이 완료되었습니다.',
        ];
    }

    private function assertEventCanBeApplied(HospitalEvent $event): void
    {
        if (! $event->isApplicationOpen()) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '신청 가능한 이벤트가 아닙니다.');
        }
    }
}
