<?php

namespace App\Domains\Nickname\Support;

use App\Domains\Nickname\Models\NicknameWord;
use Illuminate\Validation\ValidationException;

final class NicknameWordRules
{
    public static function normalize(string $word): string
    {
        $word = trim($word);

        return class_exists(\Normalizer::class) ? \Normalizer::normalize($word, \Normalizer::FORM_C) : $word;
    }

    public static function error(string $word): ?string
    {
        if (! preg_match('/^[가-힣]{1,20}$/u', $word)) {
            return '한글 완성형 1~20자로 입력해 주세요. 숫자·기호·공백은 사용할 수 없습니다.';
        }

        return null;
    }

    public function analyze(string $type, string $text): array
    {
        $tokens = preg_split('/[\s,]+/u', self::normalize($text), -1, PREG_SPLIT_NO_EMPTY);
        if (count($tokens) > 200) {
            throw ValidationException::withMessages(['text' => '한 번에 최대 200개까지 등록할 수 있습니다.']);
        }
        $existing = NicknameWord::query()->where('type', $type)->whereIn('word', $tokens)->pluck('word')->all();
        $seen = [];
        $items = [];
        foreach ($tokens as $word) {
            $error = self::error($word);
            $duplicate = isset($seen[$word]) || in_array($word, $existing, true);
            $items[] = ['word' => $word, 'state' => $error ? 'INVALID' : ($duplicate ? 'DUPLICATE' : 'NEW'), 'error' => $error];
            $seen[$word] = true;
        }

        return [
            'items' => $items,
            'new_count' => count(array_filter($items, fn ($item) => $item['state'] === 'NEW')),
            'duplicate_count' => count(array_filter($items, fn ($item) => $item['state'] === 'DUPLICATE')),
            'invalid_count' => count(array_filter($items, fn ($item) => $item['state'] === 'INVALID')),
        ];
    }
}
