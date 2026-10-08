<?php

namespace App\Domains\Common\HarmfulWord\Support;

use App\Domains\Common\HarmfulWord\Models\HarmfulWord;
use Illuminate\Validation\ValidationException;
use Normalizer;

final class HarmfulWordRules
{
    public static function normalize(string $text): string
    {
        $text = Normalizer::normalize($text, Normalizer::FORM_KC);
        $text = mb_convert_case($text, MB_CASE_FOLD, 'UTF-8');

        return preg_replace('/[\x{00AD}\x{200B}-\x{200D}\x{2060}\x{FEFF}]/u', '', $text);
    }

    public static function error(string $word): ?string
    {
        $normalized = self::normalize($word);
        if (mb_strlen($word) < 2 || mb_strlen($word) > 50 || mb_strlen($normalized) < 2 || mb_strlen($normalized) > 100) {
            return '단어는 2~50자로 입력해 주세요.';
        }
        if (preg_match('/[\p{C}\r\n]/u', $word) || ! preg_match('/[\p{L}\p{N}]/u', $normalized)) {
            return '제어 문자 없이 글자 또는 숫자를 포함해 주세요.';
        }

        return null;
    }

    public function analyze(string $text): array
    {
        $words = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/u', $text)), fn ($word) => $word !== ''));
        if (count($words) > 200 || ! $words) {
            throw ValidationException::withMessages(['text' => '한 번에 1~200개 단어를 입력해 주세요.']);
        }
        $existing = HarmfulWord::query()->whereIn('normalized_word', array_map(self::normalize(...), $words))->pluck('normalized_word')->all();
        $seen = array_fill_keys($existing, true);
        $items = [];
        $counts = ['new_count' => 0, 'duplicate_count' => 0, 'invalid_count' => 0];
        foreach ($words as $word) {
            $normalized = self::normalize($word);
            $error = self::error($word);
            $state = $error ? 'INVALID' : (isset($seen[$normalized]) ? 'DUPLICATE' : 'NEW');
            $counts[strtolower($state).'_count']++;
            $items[] = compact('word', 'state', 'error');
            $seen[$normalized] = true;
        }

        return $counts + ['items' => $items];
    }

    public function exceptions(string $word, array $phrases): array
    {
        $result = [];
        $normalizedWord = self::normalize($word);
        foreach ($phrases as $index => $phrase) {
            $phrase = trim($phrase);
            $normalized = self::normalize($phrase);
            if ($normalized === $normalizedWord || ! str_contains($normalized, $normalizedWord)
                || preg_match('/\p{C}/u', $phrase) || mb_strlen($normalized) > 200) {
                throw ValidationException::withMessages(["exceptions.$index" => '예외 표현은 금지어를 포함하는 더 긴 표현으로 입력해 주세요.']);
            }
            $result[$normalized] = ['phrase' => $phrase, 'normalized_phrase' => $normalized];
        }

        return array_values($result);
    }
}
