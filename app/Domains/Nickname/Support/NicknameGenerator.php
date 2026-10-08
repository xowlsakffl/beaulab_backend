<?php

namespace App\Domains\Nickname\Support;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\HarmfulWord\Support\HarmfulWordMatcher;
use App\Domains\Nickname\Models\NicknameWord;
use Illuminate\Validation\ValidationException;

final class NicknameGenerator
{
    public function __construct(private readonly HarmfulWordMatcher $harmfulWords) {}

    public function base(): string
    {
        $rules = $this->harmfulWords->rulesForScope('NICKNAME');
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $base = $this->randomBase();
            if (! $this->harmfulWords->findMatches($base, $rules, 1)) {
                return $base;
            }
        }

        throw ValidationException::withMessages(['nickname' => '사용 가능한 닉네임 조합이 부족합니다. 닉네임 단어와 금지어 설정을 확인해 주세요.']);
    }

    private function randomBase(): string
    {
        $parts = [];
        foreach (NicknameWord::TYPES as $type) {
            $query = NicknameWord::query()->where('type', $type)->where('status', 'ACTIVE');
            $count = (clone $query)->count();
            $word = $count ? $query->orderBy('id')->offset(random_int(0, $count - 1))->value('word') : null;
            if (! is_string($word)) {
                throw ValidationException::withMessages(['nickname' => '사용 가능한 형용사와 명사를 각각 한 개 이상 등록해 주세요.']);
            }
            $parts[] = $word;
        }

        return implode('', $parts);
    }

    public function available(string $base, bool $forceSuffix = false): string
    {
        $rules = $this->harmfulWords->rulesForScope('NICKNAME');
        if ($this->harmfulWords->findMatches($base, $rules, 1)) {
            throw ValidationException::withMessages(['nickname' => '사용할 수 없는 닉네임입니다. 다시 생성해 주세요.']);
        }
        if (! $forceSuffix && ! $this->exists($base)) {
            return $base;
        }
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $candidate = $base.random_int(1, 999999);
            if (! $this->harmfulWords->findMatches($candidate, $rules, 1) && ! $this->exists($candidate)) {
                return $candidate;
            }
        }

        throw ValidationException::withMessages(['nickname' => '닉네임을 생성하지 못했습니다. 잠시 후 다시 시도해 주세요.']);
    }

    public function preview(): array
    {
        $samples = [];
        for ($attempt = 0; $attempt < 20 && count($samples) < 5; $attempt++) {
            $samples[] = $this->available($this->base());
            $samples = array_values(array_unique($samples));
        }

        return $samples;
    }

    private function exists(string $nickname): bool
    {
        // 삭제된 회원도 DB 고유 제약에 포함되므로 같은 닉네임을 재사용하지 않는다.
        return AccountUser::withTrashed()->where('nickname', $nickname)->exists();
    }
}
