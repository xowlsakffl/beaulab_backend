<?php

namespace App\Domains\Common\PasswordReset\Queries;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class PasswordResetTokenQuery
{
    private const string TABLE = 'password_reset_tokens';

    public function recentlyCreated(string $actor, string $email, int $throttleSeconds): bool
    {
        $createdAt = DB::table(self::TABLE)
            ->where('actor', $actor)
            ->where('email', $email)
            ->value('created_at');

        if (! $createdAt) {
            return false;
        }

        return Carbon::parse($createdAt)->gt(now()->subSeconds($throttleSeconds));
    }

    public function store(string $actor, string $email, string $token): void
    {
        DB::table(self::TABLE)->updateOrInsert(
            [
                'actor' => $actor,
                'email' => $email,
            ],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );
    }

    public function valid(string $actor, string $email, string $token, int $expireMinutes): bool
    {
        $row = DB::table(self::TABLE)
            ->where('actor', $actor)
            ->where('email', $email)
            ->first(['token', 'created_at']);

        if (! $row || ! $row->created_at) {
            return false;
        }

        if (Carbon::parse($row->created_at)->lt(now()->subMinutes($expireMinutes))) {
            $this->delete($actor, $email);

            return false;
        }

        return Hash::check($token, (string) $row->token);
    }

    public function delete(string $actor, string $email): void
    {
        DB::table(self::TABLE)
            ->where('actor', $actor)
            ->where('email', $email)
            ->delete();
    }
}
