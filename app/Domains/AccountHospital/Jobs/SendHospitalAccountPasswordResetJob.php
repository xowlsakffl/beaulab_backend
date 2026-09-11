<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Jobs;

use App\Domains\AccountHospital\Actions\HospitalAccountPasswordResetSendAction;
use App\Domains\AccountHospital\Models\AccountHospital;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SendHospitalAccountPasswordResetJob implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(private readonly string $email) {}

    public function backoff(): array
    {
        return [10, 60, 120];
    }

    public function handle(HospitalAccountPasswordResetSendAction $action): void
    {
        $id = AccountHospital::query()->where('email', $this->email)->whereNotNull('email_verified_at')->value('id');
        if ($id !== null) {
            $action->execute((int) $id, expectedEmail: $this->email);
        }
    }
}
