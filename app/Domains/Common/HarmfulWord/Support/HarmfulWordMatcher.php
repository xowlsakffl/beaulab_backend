<?php

namespace App\Domains\Common\HarmfulWord\Support;

use App\Domains\Common\HarmfulWord\Models\HarmfulWord;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

final class HarmfulWordMatcher
{
    public function rulesForScope(string $scope): array
    {
        if (! in_array($scope, HarmfulWord::SCOPES, true)) {
            throw new InvalidArgumentException('지원하지 않는 금지어 적용 영역입니다.');
        }

        // 장기 캐시를 두지 않아 다음 작성 요청부터 저장된 사전이 반영된다.
        return HarmfulWord::query()->where('status', 'ACTIVE')
            ->whereHas('scopes', fn ($query) => $query->where('scope', $scope))
            ->with('exceptions')->orderBy('id')->get()->map(function (HarmfulWord $word): array {
                $literal = preg_quote($word->normalized_word, '~');
                if ($word->detect_variants) {
                    $letters = preg_split('//u', $word->normalized_word, -1, PREG_SPLIT_NO_EMPTY);
                    $literal = '';
                    foreach ($letters as $index => $letter) {
                        if ($index > 0 && preg_match('/^[\p{L}\p{N}\p{M}]{2}$/u', $letters[$index - 1].$letter)) {
                            $literal .= '[\p{Zs}._*·\-]{0,2}+';
                        }
                        $literal .= preg_quote($letter, '~');
                    }
                }
                if ($word->matching_mode === 'STANDALONE') {
                    $literal = '(?<![\p{L}\p{N}\p{M}])'.$literal.'(?![\p{L}\p{N}\p{M}])';
                }

                return [
                    'id' => $word->id, 'word' => $word->word,
                    'pattern' => '~(?=('.$literal.'))~u',
                    'exceptions' => $word->exceptions->pluck('normalized_phrase')->all(),
                ];
            })->all();
    }

    public function findMatches(string $text, array $rules, int $limit = 20): array
    {
        $text = HarmfulWordRules::normalize($text);
        $result = [];
        foreach ($rules as $rule) {
            $offset = 0;
            while (($matched = preg_match($rule['pattern'], $text, $match, PREG_OFFSET_CAPTURE, $offset)) === 1) {
                [$expression, $start] = $match[1];
                $end = $start + strlen($expression);
                $covered = false;
                foreach ($rule['exceptions'] as $exception) {
                    $from = max(0, $end - strlen($exception));
                    while (($position = strpos($text, $exception, $from)) !== false && $position <= $start) {
                        if ($position + strlen($exception) >= $end) {
                            $covered = true;
                            break 2;
                        }
                        $from = $position + 1;
                    }
                }
                if (! $covered) {
                    $result[] = ['rule_id' => $rule['id'], 'word' => $rule['word'], 'expression' => $expression];
                    if (count($result) >= $limit) {
                        return $result;
                    }
                }
                // 겹치는 출현도 검사하되 UTF-8 문자 중간에서 탐색하지 않는다.
                $offset = $start + strlen(mb_substr(substr($text, $start), 0, 1, 'UTF-8'));
            }
            if ($matched === false) {
                throw new RuntimeException('금지어 검사에 실패했습니다.');
            }
        }

        return $result;
    }

    public function assertFields(string $scope, array $fields): void
    {
        $rules = $this->rulesForScope($scope);
        $errors = [];
        foreach ($fields as $field => $value) {
            if ($this->findMatches((string) $value, $rules, 1)) {
                $errors[$field] = '사용할 수 없는 표현이 포함되어 있습니다. 내용을 수정해 주세요.';
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }
}
