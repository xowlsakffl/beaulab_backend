<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\AccountHospital;

use App\Common\Authorization\AccessRoles;
use App\Domains\AccountHospital\Actions\Hospital\HospitalAccountInvitationCompleteForHospitalAction;
use App\Domains\AccountHospital\Actions\Hospital\HospitalAccountPhoneVerificationSendAction;
use App\Domains\AccountHospital\Actions\Hospital\HospitalAccountPhoneVerificationVerifyAction;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountHospital\Models\HospitalAccountInvitation;
use App\Domains\AccountHospital\Models\HospitalAccountPhoneVerification;
use App\Domains\AccountHospital\Support\HospitalAccountInvitationToken;
use App\Domains\Common\Sms\Models\SmsBatch;
use App\Domains\Common\Sms\Models\SmsDelivery;
use App\Domains\Hospital\Models\Hospital;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;

final class HospitalAccountPhoneVerificationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication(): Application
    {
        $app = parent::createApplication();

        if ($app->configurationIsCached() || config('database.connections.mysql.database') !== 'beaulab_testing') {
            throw new \LogicException('Feature tests require uncached configuration and the beaulab_testing database.');
        }

        return $app;
    }

    public function test_sms_verification_completes_invitation_with_hospital_name(): void
    {
        Queue::fake();
        Role::query()->create([
            'name' => AccessRoles::HOSPITAL_OWNER,
            'guard_name' => 'hospital',
        ]);

        $hospital = Hospital::factory()->create([
            'name' => '뷰랩 테스트 병원',
            'ad_reception_phone_1' => '010-9999-9999',
        ]);
        $rawInvitationToken = HospitalAccountInvitationToken::make();
        $invitation = HospitalAccountInvitation::query()->create([
            'source_type' => HospitalAccountInvitation::SOURCE_HOSPITAL,
            'hospital_id' => $hospital->getKey(),
            'recipient_email' => 'hospital@example.com',
            'token_hash' => HospitalAccountInvitationToken::hash($rawInvitationToken),
            'expires_at' => now()->addHour(),
            'sent_at' => now(),
        ]);

        $sendResult = app(HospitalAccountPhoneVerificationSendAction::class)->execute(
            $rawInvitationToken,
            '010-1234-5678',
        );

        $verification = HospitalAccountPhoneVerification::query()->findOrFail($sendResult['verification_id']);
        $delivery = SmsDelivery::query()->findOrFail($verification->sms_delivery_id);
        preg_match('/\[(\d{6})\]/', (string) ($delivery->encrypted_message_body ?? $delivery->message_body), $matches);

        self::assertSame(60, $sendResult['resend_after_seconds']);
        self::assertSame('hospital_account_phone_verification', SmsBatch::query()->findOrFail($delivery->sms_batch_id)->purpose);
        self::assertNotSame($matches[1], $verification->code_hash);

        $verifyResult = app(HospitalAccountPhoneVerificationVerifyAction::class)->execute(
            $rawInvitationToken,
            (int) $verification->getKey(),
            $matches[1],
        );
        $completion = app(HospitalAccountInvitationCompleteForHospitalAction::class)->execute(
            $rawInvitationToken,
            [
                'nickname' => 'hospital_owner_test',
                'password' => 'password1234',
                'phone_verification_token' => $verifyResult['phone_verification_token'],
            ],
        );

        $account = AccountHospital::query()->findOrFail($completion['account_hospital_id']);

        self::assertSame($hospital->name, $account->name);
        self::assertSame('010-1234-5678', $account->phone);
        self::assertNotNull($account->phone_verified_at);
        self::assertNotNull($invitation->refresh()->used_at);
        self::assertNotNull($verification->refresh()->consumed_at);
    }
}
