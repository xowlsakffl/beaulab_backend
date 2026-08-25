<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountHospital\Dto\Staff\HospitalAccountInvitationForStaffDto;
use App\Domains\AccountHospital\Mail\HospitalAccountInvitationMail;
use App\Domains\AccountHospital\Models\HospitalAccountInvitation;
use App\Domains\AccountHospital\Queries\Staff\HospitalAccountInvitationForStaffQuery;
use App\Domains\AccountHospital\Support\HospitalAccountInvitationToken;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Models\HospitalBusinessRegistration;
use App\Domains\HospitalEntry\Models\HospitalEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class HospitalAccountInvitationSendForStaffAction
{
    public function __construct(
        private readonly HospitalAccountInvitationForStaffQuery $query,
    ) {}

    /** @return array{invitation: array, message: string} */
    public function execute(AccountStaff $actor, array $payload): array
    {
        Gate::authorize('create', HospitalAccountInvitation::class);

        $sourceType = (string) $payload['source_type'];
        $sourceId = (int) $payload['source_id'];
        $recipientEmail = mb_strtolower(trim((string) $payload['recipient_email']));
        $token = HospitalAccountInvitationToken::make();
        Gate::authorize('view', $this->query->findSource($sourceType, $sourceId));

        [$invitation, $hospitalName, $previousInvitationId] = DB::transaction(function () use (
            $actor,
            $sourceType,
            $sourceId,
            $recipientEmail,
            $token,
        ): array {
            $source = $this->query->lockSource($sourceType, $sourceId);
            $hospitalName = $this->validateSource($source);

            $previousInvitationId = $this->query->latestActive($sourceType, $sourceId)?->getKey();
            $this->query->revokeActive($sourceType, $sourceId);

            $invitation = $this->query->create([
                'source_type' => $sourceType,
                'hospital_id' => $source instanceof Hospital ? $source->getKey() : null,
                'hospital_entry_id' => $source instanceof HospitalEntry ? $source->getKey() : null,
                'recipient_email' => $recipientEmail,
                'token_hash' => HospitalAccountInvitationToken::hash($token),
                'expires_at' => now()->addHours((int) config('hospital_account_invitation.expire_hours', 72)),
                'created_by_staff_id' => $actor->getKey(),
            ]);

            return [$invitation, $hospitalName, $previousInvitationId];
        });

        try {
            $mail = (new HospitalAccountInvitationMail(
                hospitalName: $hospitalName,
                invitationUrl: HospitalAccountInvitationToken::url($token),
                expireHours: (int) config('hospital_account_invitation.expire_hours', 72),
            ))
                ->onConnection((string) config('hospital_account_invitation.mail.connection', 'redis'))
                ->onQueue((string) config('hospital_account_invitation.mail.queue', 'mail'));

            Mail::to($recipientEmail)->queue($mail);
            $invitation->forceFill(['sent_at' => now()])->save();
        } catch (Throwable $exception) {
            DB::transaction(function () use (
                $sourceType,
                $sourceId,
                $invitation,
                $previousInvitationId,
            ): void {
                $this->query->lockSource($sourceType, $sourceId);
                $this->query->recoverPreviousAfterFailure(
                    $sourceType,
                    $sourceId,
                    $invitation,
                    $previousInvitationId !== null ? (int) $previousInvitationId : null,
                );
            });

            throw $exception;
        }

        Log::info('병의원 계정 초대 발송', [
            'invitation_id' => $invitation->getKey(),
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'staff_id' => $actor->getKey(),
        ]);

        return [
            'invitation' => HospitalAccountInvitationForStaffDto::fromModel(
                $invitation->load('createdByStaff:id,name')
            ),
            'message' => '계정 생성 링크를 발송했습니다.',
        ];
    }

    private function validateSource(Model $source): string
    {
        if ($source instanceof Hospital) {
            if ($source->accountHospital !== null) {
                throw new CustomException(
                    ErrorCode::INVALID_REQUEST,
                    '계정 생성 이메일을 발송할 수 없는 상태입니다.',
                );
            }

            return (string) $source->name;
        }

        if (! $source instanceof HospitalEntry) {
            throw new CustomException(ErrorCode::INVALID_REQUEST);
        }

        if ($source->allow_status !== HospitalEntry::ALLOW_APPROVED) {
            throw new CustomException(
                ErrorCode::INVALID_REQUEST,
                '승인된 입점신청만 계정 초대를 발송할 수 있습니다.',
            );
        }

        if ($source->hospital_id !== null || $source->converted_at !== null) {
            throw new CustomException(
                ErrorCode::INVALID_REQUEST,
                '이미 병의원 계정 생성이 완료된 입점신청입니다.',
            );
        }

        if ($source->businessRegistrationFile === null) {
            throw new CustomException(
                ErrorCode::INVALID_REQUEST,
                '사업자등록증을 먼저 등록해 주세요.',
            );
        }

        if (Hospital::query()->where('name', $source->hospital_name)->exists()) {
            throw new CustomException(
                ErrorCode::INVALID_REQUEST,
                '동일한 병의원명이 이미 등록되어 있습니다.',
            );
        }

        if (HospitalBusinessRegistration::query()->where('business_number', $source->business_number)->exists()) {
            throw new CustomException(
                ErrorCode::INVALID_REQUEST,
                '동일한 사업자등록번호가 이미 등록되어 있습니다.',
            );
        }

        return (string) $source->hospital_name;
    }
}
