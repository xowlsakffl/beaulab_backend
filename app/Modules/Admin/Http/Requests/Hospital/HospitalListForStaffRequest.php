<?php


namespace App\Modules\Admin\Http\Requests\Hospital;

use Illuminate\Foundation\Http\FormRequest;

final class HospitalListForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        $admin = $this->user('admin');

        return $admin?->can('beaulab.hospital.list') ?? false;
    }

    public function rules(): array
    {
        return [
            'q'            => ['nullable', 'string', 'max:100'],

            'status'       => ['nullable', 'in:ACTIVE,SUSPENDED,WITHDRAWN'],
            'allow_status' => ['nullable', 'in:PENDING,APPROVED,REJECTED'],

            'sort'         => ['nullable', 'in:id,name,view_count,allow_status,status,created_at,updated_at'],
            'direction'    => ['nullable', 'in:asc,desc'],

            'page'         => ['nullable', 'integer', 'min:1'],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        $validate = $this->validated();

        return [
            'q'            => $validate['q'] ?? null,
            'status'       => $validate['status'] ?? null,
            'allow_status' => $validate['allow_status'] ?? null,

            'sort'         => $validate['sort'] ?? 'id',
            'direction'    => $validate['direction'] ?? 'desc',

            'per_page'     => (int)($validate['per_page'] ?? 15),
        ];
    }
}
