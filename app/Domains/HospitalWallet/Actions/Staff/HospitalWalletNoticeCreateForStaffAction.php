<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Sms\Actions\SmsBatchCreateAction;
use App\Domains\Common\Sms\Data\SmsDeliveryData;
use App\Domains\Common\Sms\Models\SmsDelivery;
use App\Domains\Common\Sms\Support\SmsMessage;
use App\Domains\HospitalWallet\Dto\Staff\HospitalWalletNoticeBatchForStaffDto;
use App\Domains\HospitalWallet\Models\HospitalWallet;
use App\Domains\HospitalWallet\Queries\Staff\HospitalWalletNoticeCreateForStaffQuery;
use App\Domains\HospitalWallet\Support\HospitalWalletSms;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

final class HospitalWalletNoticeCreateForStaffAction
{
    public function __construct(
        private readonly HospitalWalletNoticeCreateForStaffQuery $query,
        private readonly SmsBatchCreateAction $createSmsBatch,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('sendNotice', HospitalWallet::class);

        $actor = auth()->user();
        if (! $actor instanceof AccountStaff) {
            throw new CustomException(ErrorCode::UNAUTHORIZED);
        }

        $hospitalIds = $this->hospitalIds($payload['hospital_ids'] ?? []);
        $messageParts = HospitalWalletSms::normalizeMessageParts($payload['message_parts'] ?? []);
        $sendToManager = (bool) $payload['send_to_manager'];
        if ((bool) ($payload['send_to_representative'] ?? false)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '계정 연락처 문자 발송은 지원하지 않습니다. 담당자 수신번호를 선택해 주세요.');
        }
        $metadata = [
            'hospital_ids' => $hospitalIds,
            'message_parts' => $messageParts,
            'send_to_manager' => $sendToManager,
            'send_to_representative' => false,
        ];

        $result = $this->createSmsBatch->execute(
            purpose: HospitalWalletSms::PURPOSE_BALANCE_NOTICE,
            idempotencyKey: (string) $payload['idempotency_key'],
            messageTemplate: HospitalWalletSms::displayTemplate($messageParts),
            metadata: $metadata,
            targetCount: count($hospitalIds),
            actor: $actor,
            resolveDeliveries: function () use (
                $hospitalIds,
                $messageParts,
                $sendToManager,
            ): array {
                $wallets = $this->query->getWalletsForUpdate($hospitalIds);
                $this->assertAllWalletsExist($wallets, $hospitalIds);

                return $wallets
                    ->flatMap(fn (HospitalWallet $wallet): array => $this->deliveryRows(
                        $wallet,
                        $messageParts,
                        $sendToManager,
                    ))
                    ->values()
                    ->all();
            },
        );

        return HospitalWalletNoticeBatchForStaffDto::fromModel(
            $result->batch->load(['actor', 'deliveries.reference']),
        )->toArray() + ['replayed' => $result->replayed];
    }

    /**
     * @param  list<array{type: string, text?: string, key?: string}>  $messageParts
     * @return list<SmsDeliveryData>
     */
    private function deliveryRows(
        HospitalWallet $wallet,
        array $messageParts,
        bool $sendToManager,
    ): array {
        $hospital = $wallet->hospital;
        if (! $hospital) {
            return [];
        }

        $targets = [];
        $skipped = [];

        if ($sendToManager) {
            $this->appendTarget(
                $targets,
                $skipped,
                HospitalWalletSms::RECIPIENT_MANAGER,
                $hospital->ad_reception_phone_1,
                '담당자 광고 안내 수신번호가 없습니다.',
            );
        }

        $message = HospitalWalletSms::renderMessage($messageParts, [
            HospitalWalletSms::VARIABLE_HOSPITAL_NAME => (string) $hospital->name,
            HospitalWalletSms::VARIABLE_REMAINING_BALANCE => number_format($wallet->availableTotalBalance()),
        ]);
        $byteLength = SmsMessage::byteLength($message);

        if ($byteLength > (int) config('sms.lms_max_bytes', 2000)) {
            throw new CustomException(
                ErrorCode::INVALID_REQUEST,
                "치환된 안내 문자가 LMS 최대 용량을 초과합니다. ({$hospital->name})",
                [
                    'hospital_id' => (int) $hospital->id,
                    'byte_length' => $byteLength,
                    'max_bytes' => (int) config('sms.lms_max_bytes', 2000),
                ],
            );
        }

        $referenceType = $hospital->getMorphClass();
        $referenceId = (int) $hospital->getKey();
        $referenceLabel = (string) $hospital->name;
        $rows = collect($targets)
            ->map(fn (array $target): SmsDeliveryData => new SmsDeliveryData(
                deduplicationKey: "{$referenceType}:{$referenceId}:phone:{$target['phone_normalized']}",
                referenceType: $referenceType,
                referenceId: $referenceId,
                referenceLabel: $referenceLabel,
                recipientType: $target['recipient_type'],
                recipientId: $target['recipient_id'],
                recipientKinds: array_values(array_unique($target['recipient_kinds'])),
                phone: (string) $target['phone'],
                phoneNormalized: (string) $target['phone_normalized'],
                messageType: SmsMessage::type($message),
                messageBody: $message,
                byteLength: $byteLength,
                status: SmsDelivery::STATUS_PENDING,
            ))
            ->values()
            ->all();

        foreach ($skipped as $item) {
            $rows[] = new SmsDeliveryData(
                deduplicationKey: "{$referenceType}:{$referenceId}:recipient:{$item['recipient_kind']}",
                referenceType: $referenceType,
                referenceId: $referenceId,
                referenceLabel: $referenceLabel,
                recipientType: $item['recipient_type'],
                recipientId: $item['recipient_id'],
                recipientKinds: [$item['recipient_kind']],
                phone: null,
                phoneNormalized: null,
                messageType: null,
                messageBody: $message,
                byteLength: $byteLength,
                status: SmsDelivery::STATUS_SKIPPED,
                failedAt: now(),
                errorMessage: $item['reason'],
            );
        }

        return $rows;
    }

    private function appendTarget(
        array &$targets,
        array &$skipped,
        string $recipientKind,
        ?string $phone,
        string $missingReason,
        ?Model $recipient = null,
    ): void {
        $recipientType = $recipient?->getMorphClass();
        $recipientId = $recipient?->getKey();

        if (! SmsMessage::isSendablePhone($phone)) {
            $skipped[] = [
                'recipient_kind' => $recipientKind,
                'recipient_type' => $recipientType,
                'recipient_id' => $recipientId,
                'reason' => $missingReason,
            ];

            return;
        }

        $normalized = SmsMessage::normalizePhone($phone);
        if (isset($targets[$normalized])) {
            $targets[$normalized]['recipient_kinds'][] = $recipientKind;
            $targets[$normalized]['recipient_type'] ??= $recipientType;
            $targets[$normalized]['recipient_id'] ??= $recipientId;

            return;
        }

        $targets[$normalized] = [
            'recipient_kinds' => [$recipientKind],
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'phone' => $phone,
            'phone_normalized' => $normalized,
        ];
    }

    /**
     * @param  array<int, int|string>  $hospitalIds
     * @return list<int>
     */
    private function hospitalIds(array $hospitalIds): array
    {
        return collect($hospitalIds)
            ->map(static fn (int|string $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, HospitalWallet>  $wallets
     * @param  list<int>  $hospitalIds
     */
    private function assertAllWalletsExist(Collection $wallets, array $hospitalIds): void
    {
        $foundIds = $wallets
            ->pluck('hospital_id')
            ->map(static fn ($id): int => (int) $id)
            ->sort()
            ->values()
            ->all();

        if ($foundIds !== $hospitalIds) {
            throw new CustomException(
                ErrorCode::INVALID_REQUEST,
                '충전금 지갑이 생성되지 않은 병의원이 포함되어 있습니다.',
            );
        }
    }
}
