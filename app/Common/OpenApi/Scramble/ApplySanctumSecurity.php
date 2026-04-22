<?php

namespace App\Common\OpenApi\Scramble;

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

            $operation->addSecurity(new SecurityRequirement(['sanctum' => []]));

            return;
        }
    }
}
