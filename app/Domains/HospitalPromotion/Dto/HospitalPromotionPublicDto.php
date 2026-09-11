<?php

namespace App\Domains\HospitalPromotion\Dto;

use App\Domains\HospitalPromotion\Models\HospitalPromotion;
use Illuminate\Support\Arr;

final readonly class HospitalPromotionPublicDto
{
    private function __construct(private array $data) {}

    public static function fromModel(HospitalPromotion $promotion, string $today, bool $detail = false): self
    {
        return new self(Arr::only(HospitalPromotionDto::fromModel($promotion, $today, $detail)->toArray(), [
            'id', 'title', 'side', 'slot', 'start_date', 'end_date', 'banner', ...($detail ? ['content'] : []),
        ]));
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
