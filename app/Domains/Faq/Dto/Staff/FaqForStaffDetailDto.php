<?php

namespace App\Domains\Faq\Dto\Staff;

use App\Domains\Faq\Models\Faq;

/**
 * FaqForStaffDetailDto 역할 정의.
 * FAQ 도메인의 DTO로, 모델 값을 API 응답이나 계층 간 전달에 맞는 단순한 배열/값 구조로 정규화한다.
 */
final readonly class FaqForStaffDetailDto
{
    public function __construct(public array $faq) {}

    public static function fromModel(Faq $faq): self
    {
        $data = FaqForStaffDto::fromModel($faq)->toArray();

        $data['content'] = (string) $faq->content;
        $data['creator'] = $faq->relationLoaded('creator') && $faq->creator
            ? [
                'id' => (int) $faq->creator->id,
                'name' => (string) $faq->creator->name,
                'email' => (string) $faq->creator->email,
            ]
            : null;
        $data['updater'] = $faq->relationLoaded('updater') && $faq->updater
            ? [
                'id' => (int) $faq->updater->id,
                'name' => (string) $faq->updater->name,
                'email' => (string) $faq->updater->email,
            ]
            : null;

        return new self($data);
    }

    public function toArray(): array
    {
        return $this->faq;
    }
}
