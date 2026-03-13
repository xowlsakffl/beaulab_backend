<?php

namespace App\Domains\Faq\Dto\Staff;

use App\Domains\Faq\Models\Faq;

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
