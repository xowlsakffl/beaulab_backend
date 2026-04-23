<?php

namespace App\Common\OpenApi\Scramble;

use Dedoc\Scramble\Contracts\DocumentTransformer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;

final class AddSanctumSecurityScheme implements DocumentTransformer
{
    public function handle(OpenApi $document, OpenApiContext $context): void
    {
        $document->components->addSecurityScheme(
            'sanctum',
            SecurityScheme::http('bearer', 'Sanctum token')
                ->as('sanctum')
                ->setDescription('Sanctum 개인 접근 토큰입니다. Bearer 접두어 없이 토큰 값만 입력합니다.'),
        );
    }
}
