<?php

namespace App\Domains\HospitalPromotion\Dto;

use App\Domains\HospitalPromotion\Models\HospitalPromotion;
use Illuminate\Support\Collection;

final readonly class HospitalPromotionAvailabilityDto
{
    public const LIMIT = 10;

    public const MESSAGE = '일정이 겹치는 프로모션이 있습니다. 확인 바랍니다.';

    public function __construct(private array $data) {}

    public static function fromModels(Collection $promotions, bool $canView): self
    {
        return new self([
            'available' => $promotions->isEmpty(),
            'conflicts' => $canView ? $promotions->take(self::LIMIT)->map(fn (HospitalPromotion $promotion) => [
                'id' => (int) $promotion->id,
                'title' => $promotion->title,
                'side' => $promotion->side,
                'slot' => (int) $promotion->slot,
                'start_date' => $promotion->start_date->toDateString(),
                'end_date' => $promotion->end_date->toDateString(),
            ])->values()->all() : [],
            'has_more' => $canView && $promotions->count() > self::LIMIT,
        ]);
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
