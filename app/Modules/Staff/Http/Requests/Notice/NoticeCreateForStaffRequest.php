<?php

namespace App\Modules\Staff\Http\Requests\Notice;

use App\Domains\Notice\Models\Notice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * NoticeCreateForStaffRequest 역할 정의.
 * 공지사항 도메인의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class NoticeCreateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        foreach ([
            'publish_start_at',
            'publish_end_at',
            'is_publish_period_unlimited',
            'status',
            'is_pinned',
            'is_important',
        ] as $nullableKey) {
            if (array_key_exists($nullableKey, $data) && $data[$nullableKey] === '') {
                $data[$nullableKey] = null;
            }
        }

        $this->replace($data);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel' => ['required', Rule::in(Notice::channels())],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'status' => ['sometimes', 'string', Rule::in(Notice::statuses())],
            'is_pinned' => ['nullable', 'boolean'],
            'is_publish_period_unlimited' => ['nullable', 'boolean'],
            'publish_start_at' => ['nullable', 'date'],
            'publish_end_at' => ['nullable', 'date', 'after_or_equal:publish_start_at'],
            'is_important' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:20480'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'channel' => '공지 채널',
            'title' => '제목',
            'content' => '내용',
            'status' => '운영 상태',
            'is_pinned' => '상단 공지 여부',
            'is_publish_period_unlimited' => '게시기간 무제한 여부',
            'publish_start_at' => '게시 시작 일시',
            'publish_end_at' => '게시 종료 일시',
            'is_important' => '관리자 메인 팝업 여부',
            'attachments' => '첨부파일 목록',
            'attachments.*' => '첨부파일',
        ];
    }
}
