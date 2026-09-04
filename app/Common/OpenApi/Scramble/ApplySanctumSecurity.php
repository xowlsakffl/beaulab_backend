<?php

namespace App\Common\OpenApi\Scramble;

use App\Common\Auth\AuthActor;
use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use Dedoc\Scramble\Support\RouteInfo;

final class ApplySanctumSecurity extends OperationExtension
{
    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        foreach ($routeInfo->route->gatherMiddleware() as $middleware) {
            if (! is_string($middleware) || ! str_contains($middleware, 'auth:sanctum')) {
                continue;
            }

            if ($routeInfo->route->uri() === 'broadcasting/auth') {
                $operation->addSecurity(new SecurityRequirement(['sanctum' => []]));

                return;
            }

            $actor = AuthActor::tryFrom(explode('/', $routeInfo->route->uri())[2] ?? '');
            if ($actor) {
                $security = [$actor->value.'Session' => [], 'webClient' => []];
                if (array_diff($routeInfo->route->methods(), ['GET', 'HEAD', 'OPTIONS'])) {
                    $security['csrf'] = [];
                }
                $operation->addSecurity(new SecurityRequirement($security));
            }
            if ($actor === AuthActor::USER && ! in_array('web.session', $routeInfo->route->gatherMiddleware(), true)) {
                $operation->addSecurity(new SecurityRequirement(['sanctum' => []]));
            }

            return;
        }
    }
}
