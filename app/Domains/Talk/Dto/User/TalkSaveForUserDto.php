<?php

namespace App\Domains\Talk\Dto\User;

use App\Domains\Talk\Models\Talk;

/**
 * 사용자 토크 저장 응답 DTO.
 */
final readonly class TalkSaveForUserDto
{
    public function __construct(
        public int $talkId,
        public bool $isSaved,
        public int $saveCount,
    ) {}

    public static function fromTalk(Talk $talk, bool $isSaved): self
    {
        return new self(
            talkId: (int) $talk->id,
            isSaved: $isSaved,
            saveCount: (int) $talk->save_count,
        );
    }

    public function toArray(): array
    {
        return [
            'talk_id' => $this->talkId,
            'is_saved' => $this->isSaved,
            'save_count' => $this->saveCount,
        ];
    }
}
