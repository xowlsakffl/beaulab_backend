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
        foreach (\App\Common\Auth\AuthActor::cases() as $actor) {
            $name = $actor->value.'Session';
            $document->components->addSecurityScheme($name, SecurityScheme::apiKey('cookie', 'beaulab_'.$actor->value.'_session')->as($name)
                ->setDescription('웹 세션 쿠키입니다. 로그인 및 변경 요청에는 CSRF 헤더가 필요합니다.'));
        }
        $document->components->addSecurityScheme('webClient', SecurityScheme::apiKey('header', 'X-Beaulab-Client')->as('webClient')->setDescription('웹 요청은 web 값을 사용합니다.'));
        $document->components->addSecurityScheme('csrf', SecurityScheme::apiKey('header', 'X-CSRF-TOKEN')->as('csrf')->setDescription('해당 actor의 auth/csrf 응답에서 받은 토큰입니다.'));
    }
}
