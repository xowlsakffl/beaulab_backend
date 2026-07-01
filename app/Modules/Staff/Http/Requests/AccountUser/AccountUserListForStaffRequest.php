<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\AccountUser;

use App\Domains\AccountUser\Models\AccountUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * AccountUserListForStaffRequest 역할 정의.
 * 일반 회원 계정 도메인의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class AccountUserListForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        // 이미 라우트에서 검사함
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'summary_filter' => ['nullable', Rule::in(['withdrawn', 'blocked', 'warned'])],
            'date_type' => ['nullable', 'in:created_at,last_accessed_at'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],

            'signup_channel' => ['nullable', Rule::in(AccountUser::signupChannels())],
            'status' => ['nullable', Rule::in(AccountUser::statuses())],
            'warning_count_min' => ['nullable', 'integer', 'min:0'],
            'warning_count_max' => ['nullable', 'integer', 'min:0'],

            'sort' => ['nullable', 'in:id,email,nickname,name,signup_channel,status,warning_count,created_at,last_accessed_at,last_access_ip'],
            'direction' => ['nullable', 'in:asc,desc'],

            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $min = $this->input('warning_count_min');
            $max = $this->input('warning_count_max');

            if ($min === null || $min === '' || $max === null || $max === '') {
                return;
            }

            if ((int) $max < (int) $min) {
                $validator->errors()->add('warning_count_max', '경고횟수 최대값은 최소값보다 크거나 같아야 합니다.');
            }
        });
    }

    public function filters(): array
    {
        $validate = $this->validated();

        return [
            'q' => $validate['q'] ?? null,
            'summary_filter' => $validate['summary_filter'] ?? null,
            'date_type' => $validate['date_type'] ?? 'created_at',
            'start_date' => $validate['start_date'] ?? null,
            'end_date' => $validate['end_date'] ?? null,
            'signup_channel' => $validate['signup_channel'] ?? null,
            'status' => $validate['status'] ?? null,
            'warning_count_min' => $validate['warning_count_min'] ?? null,
            'warning_count_max' => $validate['warning_count_max'] ?? null,

            'sort' => $validate['sort'] ?? 'id',
            'direction' => $validate['direction'] ?? 'desc',

            'per_page' => (int) ($validate['per_page'] ?? 15),
        ];
    }

    public function attributes(): array
    {
        return [
            'q' => '검색어',
            'summary_filter' => '요약 필터',
            'date_type' => '기간 기준',
            'start_date' => '시작일',
            'end_date' => '종료일',
            'signup_channel' => '가입경로',
            'status' => '회원상태',
            'warning_count_min' => '경고횟수 최소값',
            'warning_count_max' => '경고횟수 최대값',
            'sort' => '정렬 기준',
            'direction' => '정렬 방향',
            'page' => '페이지',
            'per_page' => '페이지당 개수',
        ];
    }
}
