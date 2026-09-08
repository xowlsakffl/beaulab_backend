<?php

declare(strict_types=1);

namespace App\Domains\Common\Media\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

final class EditorHtmlSanitizer
{
    private static ?HtmlSanitizer $sanitizer = null;

    public static function clean(string $content): string
    {
        self::$sanitizer ??= new HtmlSanitizer(
            (new HtmlSanitizerConfig)
                ->allowSafeElements()
                ->allowElement('img', ['src', 'alt', 'title', 'width', 'height'])
                ->allowLinkSchemes(['http', 'https', 'mailto'])
                ->allowMediaSchemes(['http', 'https'])
                ->allowRelativeLinks()
                ->allowRelativeMedias()
                ->forceAttribute('a', 'rel', 'noopener noreferrer')
                ->withMaxInputLength(2000000),
        );

        return self::$sanitizer->sanitize(trim($content));
    }
}
