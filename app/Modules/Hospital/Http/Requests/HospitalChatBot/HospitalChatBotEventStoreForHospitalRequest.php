<?php

declare(strict_types=1);

namespace App\Modules\Hospital\Http\Requests\HospitalChatBot;

use App\Domains\HospitalChatBot\Models\HospitalChatBotEvent;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 이용 이벤트 수신. 본문은 이벤트 배열이다. 한 건이어도 배열이다.
 *
 * 나중에 묶음 전송으로 바꿔도 계약이 바뀌지 않게 처음부터 배열로 받는다.
 */
final class HospitalChatBotEventStoreForHospitalRequest extends FormRequest
{
    public const MAX_BATCH = 20;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            '*' => ['required', 'array'],
            '*.event_type' => ['required', 'string', Rule::in(HospitalChatBotEvent::TYPES)],
            // 소프트 삭제된 Q&A도 허용한다. 삭제 직후에 열려 있던 화면에서 온 클릭을 버릴 이유가 없다.
            '*.qna_id' => [
                'nullable', 'integer', 'min:1',
                'required_if:*.event_type,'.HospitalChatBotEvent::TYPE_QNA_VIEWED,
                Rule::exists('hospital_chatbot_qnas', 'id'),
            ],
            '*.source_page_path' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return list<\Closure(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $payload = $this->all();

                if (! array_is_list($payload)) {
                    $validator->errors()->add('events', '이벤트는 배열로 보내야 합니다.');

                    return;
                }

                if (count($payload) > self::MAX_BATCH) {
                    $validator->errors()->add('events', '한 번에 최대 '.self::MAX_BATCH.'건까지 보낼 수 있습니다.');
                }
            },
        ];
    }

    /** @return list<array{event_type:string, qna_id:?int, source_page_path:?string}> */
    public function events(): array
    {
        $events = [];

        foreach ($this->validated() as $item) {
            $type = (string) $item['event_type'];
            $qnaId = $item['qna_id'] ?? null;

            $events[] = [
                'event_type' => $type,
                // 열람 이벤트에 실려 온 qna_id는 뜻이 없다. 버린다.
                'qna_id' => $type === HospitalChatBotEvent::TYPE_QNA_VIEWED && is_numeric($qnaId) ? (int) $qnaId : null,
                'source_page_path' => self::text($item['source_page_path'] ?? null),
            ];
        }

        return $events;
    }

    public function attributes(): array
    {
        return [
            '*.event_type' => '이벤트 종류',
            '*.qna_id' => 'Q&A ID',
            '*.source_page_path' => '화면 경로',
        ];
    }

    private static function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
