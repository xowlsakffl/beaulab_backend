<?php

namespace App\Domains\HospitalPromotion\Dto;

use App\Domains\Common\Media\Support\EditorHtmlSanitizer;
use App\Domains\HospitalPromotion\Models\HospitalPromotion;

final readonly class HospitalPromotionDto
{
    private function __construct(private array $data) {}

    public static function fromModel(HospitalPromotion $promotion, string $today, bool $detail = false): self
    {
        $banner = $promotion->relationLoaded('banner') ? $promotion->banner : null;
        $creator = $promotion->relationLoaded('creator') ? $promotion->creator : null;
        $data = [
            'id' => (int) $promotion->id,
            'title' => (string) $promotion->title,
            'side' => (string) $promotion->side,
            'slot' => (int) $promotion->slot,
            'start_date' => $promotion->start_date->toDateString(),
            'end_date' => $promotion->end_date->toDateString(),
            'status' => (string) $promotion->status,
            'progress' => $promotion->progress($today),
            'click_count' => (int) $promotion->click_count,
            'banner' => $banner ? [
                'id' => (int) $banner->id, 'path' => $banner->publicPath(),
                'file_name' => basename((string) $banner->path),
                'size' => $banner->size, 'mime_type' => $banner->mime_type,
                'width' => $banner->width, 'height' => $banner->height,
            ] : null,
            'creator' => $creator ? ['id' => (int) $creator->id, 'name' => (string) $creator->name] : null,
            'created_at' => $promotion->created_at?->toISOString(),
            'updated_at' => $promotion->updated_at?->toISOString(),
        ];
        if ($detail) {
            $data['content'] = EditorHtmlSanitizer::clean((string) $promotion->content);
        }

        return new self($data);
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
