<?php

namespace App\Domains\RemoteConsultation\Dto;

use App\Domains\Common\Media\Models\Media;
use App\Domains\RemoteConsultation\Models\RemoteConsultation;
use App\Domains\RemoteConsultation\Models\RemoteConsultationQuote;

final class RemoteConsultationForStaffDto
{
    private static function image(Media $media): array
    {
        return ['id' => $media->id, 'url' => $media->publicUrl(), 'slot' => $media->metadata['slot'] ?? null, 'collection' => $media->collection];
    }

    public static function row(RemoteConsultation $c): array
    {
        $image = $c->media->firstWhere('collection', 'member_images');

        return ['id' => $c->id, 'created_at' => $c->created_at?->toIso8601String(), 'name' => $c->name, 'birth_date' => $c->birth_date?->format('Y-m-d'), 'gender' => $c->gender, 'phone' => $c->phone, 'approval_status' => $c->approval_status, 'categories' => $c->categories->map(fn ($v) => ['id' => $v->id, 'name' => $v->pivot->category_name])->values()->all(), 'image' => $image ? self::image($image) : null, 'answer_count' => (int) ($c->answer_count ?? 0), 'assigned_count' => (int) ($c->quotes_count ?? 0)];
    }

    public static function detail(RemoteConsultation $c, array $requestedHospitals): array
    {
        $c->setAttribute('answer_count', $c->quotes->where('status', 'ANSWERED')->count());
        $c->setAttribute('quotes_count', $c->quotes->count());

        return [...self::row($c), 'surgery_timing' => $c->surgery_timing, 'height_cm' => $c->height_cm, 'budget_amount' => $c->budget_amount, 'special_conditions' => $c->special_conditions, 'desired_style' => $c->desired_style, 'inquiry' => $c->inquiry, 'approved_at' => $c->approved_at?->toIso8601String(), 'approved_by' => $c->approver?->name, 'requested_hospitals' => $requestedHospitals, 'images' => $c->media->map(fn ($m) => self::image($m))->all(), 'quotes' => $c->quotes->map(fn ($q) => self::quote($q, false))->all()];
    }

    public static function quote(RemoteConsultationQuote $q, bool $full = true): array
    {
        $answered = $q->status === 'ANSWERED';
        $data = ['id' => $q->id, 'hospital_id' => $q->hospital_id, 'hospital_name' => $q->hospital_name, 'selection_type' => $q->selection_type, 'status' => $q->status, 'matched_categories' => $q->matched_categories, 'quoted_categories' => $answered ? ($q->quoted_categories ?? []) : [], 'total_amount' => $answered ? $q->total_amount : null, 'answered_at' => $q->answered_at?->toIso8601String(), 'recommended_events' => $answered ? ($q->recommended_events ?? []) : []];

        return $full ? [...$data, 'answer' => $answered ? $q->answer : null] : $data;
    }
}
