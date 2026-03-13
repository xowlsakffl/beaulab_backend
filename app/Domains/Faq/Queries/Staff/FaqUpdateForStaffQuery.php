<?php

namespace App\Domains\Faq\Queries\Staff;

use App\Domains\Faq\Models\Faq;

final class FaqUpdateForStaffQuery
{
    public function update(Faq $faq, array $payload): Faq
    {
        $faq->fill([
            'channel' => array_key_exists('channel', $payload) ? (string) $payload['channel'] : $faq->channel,
            'question' => array_key_exists('question', $payload) ? (string) $payload['question'] : $faq->question,
            'content' => array_key_exists('content', $payload) ? (string) $payload['content'] : $faq->content,
            'status' => array_key_exists('status', $payload) ? (string) $payload['status'] : $faq->status,
            'sort_order' => array_key_exists('sort_order', $payload) ? (int) $payload['sort_order'] : $faq->sort_order,
            'updated_by_staff_id' => $payload['updated_by_staff_id'] ?? $faq->updated_by_staff_id,
        ]);

        if ($faq->isDirty()) {
            $faq->save();
        }

        return $faq->fresh();
    }
}
