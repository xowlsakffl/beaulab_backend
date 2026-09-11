<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\AccountHospital;

use App\Common\Authorization\AccessRoles;
use App\Domains\AccountHospital\Actions\Hospital\HospitalAccountEmailVerificationSendAction;
use App\Domains\AccountHospital\Actions\Hospital\HospitalAccountEmailVerificationVerifyAction;
use App\Domains\AccountHospital\Actions\Hospital\HospitalAccountInvitationCompleteForHospitalAction;
use App\Domains\AccountHospital\Mail\HospitalAccountEmailVerificationMail;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountHospital\Models\HospitalAccountEmailVerification;
use App\Domains\AccountHospital\Models\HospitalAccountInvitation;
use App\Domains\AccountHospital\Support\HospitalAccountInvitationToken;
use App\Domains\Hospital\Models\Hospital;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

final class HospitalAccountEmailVerificationFlowTest extends TestCase
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

    public function test_email_verification_completes_invitation_with_hospital_name(): void
    {
        Mail::fake();
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

        $sendResult = app(HospitalAccountEmailVerificationSendAction::class)->execute(
            $rawInvitationToken,
            'owner@example.com',
        );

        $verification = HospitalAccountEmailVerification::query()->findOrFail($sendResult['verification_id']);
        $code = '';
        Mail::assertQueued(HospitalAccountEmailVerificationMail::class, function ($mail) use (&$code) {
            $code = $mail->content()->with['code'];

            return true;
        });
        self::assertSame(60, $sendResult['resend_after_seconds']);
        self::assertNotSame($code, $verification->code_hash);

        $verifyResult = app(HospitalAccountEmailVerificationVerifyAction::class)->execute(
            $rawInvitationToken,
            (int) $verification->getKey(),
            $code,
        );
        $completion = app(HospitalAccountInvitationCompleteForHospitalAction::class)->execute(
            $rawInvitationToken,
            [
                'nickname' => 'hospital_owner_test',
                'password' => 'password1234',
                'email' => 'owner@example.com',
                'email_verification_token' => $verifyResult['email_verification_token'],
            ],
        );

        $account = AccountHospital::query()->findOrFail($completion['account_hospital_id']);

        self::assertSame($hospital->name, $account->name);
        self::assertSame('owner@example.com', $account->email);
        self::assertNotNull($account->email_verified_at);
        self::assertSame('010-9999-9999', $hospital->refresh()->ad_reception_phone_1);
        self::assertNotNull($invitation->refresh()->used_at);
        self::assertNotNull($verification->refresh()->consumed_at);
    }
}
