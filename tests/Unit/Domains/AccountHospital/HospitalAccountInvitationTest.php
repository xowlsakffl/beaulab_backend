<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\AccountHospital;

use App\Common\Support\ActorDisplay;
use App\Domains\AccountHospital\Actions\Hospital\HospitalAccountEmailVerificationSendAction;
use App\Domains\AccountHospital\Actions\Hospital\HospitalAccountEmailVerificationVerifyAction;
use App\Domains\AccountHospital\Actions\Hospital\HospitalAccountInvitationCompleteForHospitalAction;
use App\Domains\AccountHospital\Actions\Hospital\HospitalAccountInvitationGetForHospitalAction;
use App\Domains\AccountHospital\Actions\Staff\HospitalAccountInvitationListForStaffAction;
use App\Domains\AccountHospital\Actions\Staff\HospitalAccountInvitationSendForStaffAction;
use App\Domains\AccountHospital\Mail\HospitalAccountInvitationMail;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountHospital\Models\HospitalAccountEmailVerification;
use App\Domains\AccountHospital\Models\HospitalAccountInvitation;
use App\Domains\AccountHospital\Policies\HospitalAccountInvitationPolicy;
use App\Domains\AccountHospital\Support\AccountHospitalEmail;
use App\Domains\AccountHospital\Support\HospitalAccountEmailVerificationCode;
use App\Domains\AccountHospital\Support\HospitalAccountEmailVerificationToken;
use App\Domains\AccountHospital\Support\HospitalAccountInvitationToken;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalEntry\Models\HospitalEntry;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

final class HospitalAccountInvitationTest extends TestCase
{
    public function test_invitation_status_follows_usage_revocation_and_expiration(): void
    {
        $invitation = new HospitalAccountInvitation;
        $invitation->setRawAttributes([
            'source_type' => HospitalAccountInvitation::SOURCE_HOSPITAL,
            'expires_at' => Carbon::now()->addHour()->format('Y-m-d H:i:s'),
        ]);

        self::assertTrue($invitation->isActive());
        self::assertSame(HospitalAccountInvitation::STATUS_ACTIVE, $invitation->status());

        $invitation->setRawAttributes([
            ...$invitation->getAttributes(),
            'revoked_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
        self::assertFalse($invitation->isActive());
        self::assertSame(HospitalAccountInvitation::STATUS_REVOKED, $invitation->status());

        $invitation->setRawAttributes([
            ...$invitation->getAttributes(),
            'revoked_at' => null,
            'used_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
        self::assertSame(HospitalAccountInvitation::STATUS_USED, $invitation->status());

        $invitation->setRawAttributes([
            ...$invitation->getAttributes(),
            'used_at' => null,
            'expires_at' => Carbon::now()->subMinute()->format('Y-m-d H:i:s'),
        ]);
        self::assertSame(HospitalAccountInvitation::STATUS_EXPIRED, $invitation->status());

        self::assertSame('사용 가능', HospitalAccountInvitation::statusLabel(HospitalAccountInvitation::STATUS_ACTIVE));
        self::assertSame('사용 완료', HospitalAccountInvitation::statusLabel(HospitalAccountInvitation::STATUS_USED));
        self::assertSame('폐기', HospitalAccountInvitation::statusLabel(HospitalAccountInvitation::STATUS_REVOKED));
        self::assertSame('만료', HospitalAccountInvitation::statusLabel(HospitalAccountInvitation::STATUS_EXPIRED));
    }

    public function test_tokens_are_stored_as_sha256_hashes(): void
    {
        $invitationToken = HospitalAccountInvitationToken::make();
        $emailVerificationToken = HospitalAccountEmailVerificationToken::make();

        self::assertSame(64, strlen($invitationToken));
        self::assertSame(64, strlen($emailVerificationToken));
        self::assertSame(hash('sha256', $invitationToken), HospitalAccountInvitationToken::hash($invitationToken));
        self::assertSame(hash('sha256', $emailVerificationToken), HospitalAccountEmailVerificationToken::hash($emailVerificationToken));
        self::assertNotSame($emailVerificationToken, HospitalAccountEmailVerificationToken::hash($emailVerificationToken));
    }

    public function test_email_verification_is_short_lived_and_single_use(): void
    {
        $verification = new HospitalAccountEmailVerification;
        $verification->setRawAttributes([
            'verified_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'verification_expires_at' => Carbon::now()->addMinutes(15)->format('Y-m-d H:i:s'),
        ]);

        self::assertTrue($verification->isVerificationUsable());

        $verification->setRawAttributes([
            ...$verification->getAttributes(),
            'consumed_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
        self::assertFalse($verification->isVerificationUsable());
    }

    public function test_verified_email_code_cannot_be_reused(): void
    {
        $verification = new HospitalAccountEmailVerification;
        $verification->setRawAttributes([
            'code_expires_at' => Carbon::now()->addMinutes(5)->format('Y-m-d H:i:s'),
            'verified_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        self::assertFalse($verification->isCodeUsable());
    }

    public function test_email_verification_code_is_six_digits_and_hashed(): void
    {
        $code = HospitalAccountEmailVerificationCode::make();
        $hash = HospitalAccountEmailVerificationCode::hash($code);

        self::assertMatchesRegularExpression('/^\d{6}$/', $code);
        self::assertNotSame($code, $hash);
        self::assertTrue(HospitalAccountEmailVerificationCode::verify($code, $hash));
        self::assertFalse(HospitalAccountEmailVerificationCode::verify('not-code', $hash));
    }

    public function test_verified_email_is_normalized_for_account_storage(): void
    {
        self::assertTrue(AccountHospitalEmail::isValid('owner@example.com'));
        self::assertSame('owner@example.com', AccountHospitalEmail::normalize(' Owner@Example.COM '));
        self::assertFalse(AccountHospitalEmail::isValid('02-123-4567'));
    }

    public function test_account_exposes_only_a_verified_email(): void
    {
        $account = new AccountHospital(['email' => 'owner@example.com']);

        self::assertNull($account->verifiedEmail());

        $account->email_verified_at = now();

        self::assertSame('owner@example.com', $account->verifiedEmail());
    }

    public function test_hospital_account_actor_uses_hospital_name_instead_of_account_name(): void
    {
        $account = new AccountHospital(['name' => '저장된 이전 병원명']);
        $account->setRelation('hospital', new Hospital(['name' => '현재 병원명']));

        self::assertSame('현재 병원명', ActorDisplay::name($account));
    }

    public function test_laravel_discovers_the_invitation_policy(): void
    {
        self::assertInstanceOf(
            HospitalAccountInvitationPolicy::class,
            Gate::getPolicyFor(HospitalAccountInvitation::class),
        );
    }

    public function test_invitation_actions_are_resolvable_from_the_container(): void
    {
        self::assertInstanceOf(HospitalAccountInvitationGetForHospitalAction::class, app(HospitalAccountInvitationGetForHospitalAction::class));
        self::assertInstanceOf(HospitalAccountInvitationCompleteForHospitalAction::class, app(HospitalAccountInvitationCompleteForHospitalAction::class));
        self::assertInstanceOf(HospitalAccountEmailVerificationSendAction::class, app(HospitalAccountEmailVerificationSendAction::class));
        self::assertInstanceOf(HospitalAccountEmailVerificationVerifyAction::class, app(HospitalAccountEmailVerificationVerifyAction::class));
        self::assertInstanceOf(HospitalAccountInvitationListForStaffAction::class, app(HospitalAccountInvitationListForStaffAction::class));
        self::assertInstanceOf(HospitalAccountInvitationSendForStaffAction::class, app(HospitalAccountInvitationSendForStaffAction::class));
    }

    public function test_invitation_mail_encrypts_the_queued_token_payload(): void
    {
        self::assertInstanceOf(
            ShouldBeEncrypted::class,
            new HospitalAccountInvitationMail('테스트 병의원', 'https://example.com/invitation', 72),
        );
    }

    public function test_hospital_entry_does_not_collect_a_default_doctor_name(): void
    {
        self::assertNotContains('doctor_name', (new HospitalEntry)->getFillable());
    }
}
