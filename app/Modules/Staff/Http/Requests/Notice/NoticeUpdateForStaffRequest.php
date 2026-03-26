<?php

namespace App\Modules\Staff\Http\Requests\Notice;

use App\Domains\Common\Models\Media\Media;
use App\Domains\Notice\Models\Notice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class NoticeUpdateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        foreach ([
            'channel',
            'title',
            'content',
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

        if (array_key_exists('existing_attachment_ids', $data)) {
            $data['existing_attachment_ids'] = $this->normalizeIdList($data['existing_attachment_ids']);
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
            'channel' => ['sometimes', Rule::in(Notice::channels())],
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
            'status' => ['sometimes', 'string', Rule::in(Notice::statuses())],
            'is_pinned' => ['sometimes', 'nullable', 'boolean'],
            'is_publish_period_unlimited' => ['sometimes', 'nullable', 'boolean'],
            'publish_start_at' => ['sometimes', 'nullable', 'date'],
            'publish_end_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:publish_start_at'],
            'is_important' => ['sometimes', 'nullable', 'boolean'],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:20480'],
            'existing_attachment_ids' => ['sometimes', 'array', 'max:5'],
            'existing_attachment_ids.*' => ['integer', 'distinct', $this->mediaBelongsToNoticeRule('attachments')],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $keptAttachmentCount = count($this->input('existing_attachment_ids', []));
            $newAttachmentCount = $this->countUploadedFiles($this->file('attachments'));

            if ($keptAttachmentCount + $newAttachmentCount > 5) {
                $validator->errors()->add('attachments', '첨부파일은 최대 5개까지 등록할 수 있습니다.');
            }
        });
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
            'existing_attachment_ids' => '기존 첨부파일 목록',
            'existing_attachment_ids.*' => '기존 첨부파일',
        ];
    }

    private function mediaBelongsToNoticeRule(string $collection): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($collection): void {
            if ($value === null || $value === '') {
                return;
            }

            $notice = $this->route('notice');

            if (! $notice instanceof Notice) {
                $fail('공지사항 정보를 확인할 수 없습니다.');
                return;
            }

            $exists = Media::query()
                ->whereKey((int) $value)
                ->where('model_type', Notice::class)
                ->where('model_id', $notice->getKey())
                ->where('collection', $collection)
                ->exists();

            if (! $exists) {
                $fail('선택한 기존 파일 정보가 올바르지 않습니다.');
            }
        };
    }

    /**
     * @return array<int, int>
     */
    private function normalizeIdList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(static fn ($item): bool => is_int($item) || (is_string($item) && ctype_digit(trim($item))))
            ->map(static fn ($item): int => (int) $item)
            ->filter(static fn (int $item): bool => $item > 0)
            ->values()
            ->all();
    }

    private function countUploadedFiles(mixed $files): int
    {
        if ($files === null) {
            return 0;
        }

        if (is_array($files)) {
            return count($files);
        }

        return 1;
    }
}
