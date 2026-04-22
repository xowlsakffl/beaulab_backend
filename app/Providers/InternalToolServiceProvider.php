<?php

namespace App\Providers;

use App\Common\Authorization\AccessRoles;
use App\Common\OpenApi\Scramble\AddSanctumSecurityScheme;
use App\Common\OpenApi\Scramble\ApplySanctumSecurity;
use App\Common\OpenApi\Scramble\NormalizeQueryArrayParameters;
use App\Domains\AccountStaff\Models\AccountStaff;
use Dedoc\Scramble\Scramble;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class InternalToolServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Scramble::configure()
            ->withDocumentTransformers(AddSanctumSecurityScheme::class)
            ->withOperationTransformers([
                ApplySanctumSecurity::class,
                NormalizeQueryArrayParameters::class,
            ]);

        Gate::define('viewTool', function (?AccountStaff $user): bool {
            if (! $user instanceof AccountStaff || ! $user->isActive()) {
                return false;
            }

            if (! $user->hasAnyRole([
                AccessRoles::BEAULAB_SUPER_ADMIN,
                AccessRoles::BEAULAB_DEV,
            ])) {
                return false;
            }

            $allowedEmails = $this->allowedEmails();
            if ($allowedEmails === []) {
                return true;
            }

            return in_array(mb_strtolower((string) $user->email), $allowedEmails, true);
        });
    }

    /**
     * @return array<int, string>
     */
    private function allowedEmails(): array
    {
        $configured = (string) env('INTERNAL_TOOL_ALLOWED_EMAILS', '');

        if ($configured === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (string $email): string => mb_strtolower(trim($email)),
            explode(',', $configured)
        )));
    }
}
