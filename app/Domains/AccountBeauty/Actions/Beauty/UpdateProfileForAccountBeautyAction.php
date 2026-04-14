<?php

namespace App\Domains\AccountBeauty\Actions\Beauty;

use App\Domains\AccountBeauty\Dto\Beauty\ProfileForAccountBeautyDto;
use App\Domains\AccountBeauty\Models\AccountBeauty;
use App\Domains\AccountBeauty\Queries\Beauty\UpdateProfileForAccountBeautyQuery;
use Illuminate\Support\Facades\Log;

/**
 * 뷰티 계정 프로필 수정 유스케이스.
 * 저장은 Query에 위임하고 변경된 프로필 DTO를 반환한다.
 */
final class UpdateProfileForAccountBeautyAction
{
    public function __construct(
        private readonly UpdateProfileForAccountBeautyQuery $query,
    ) {}

    /**
     * @param array{name?:string,email?:string} $filters
     * @return array{profile: array}
     */
    public function execute(AccountBeauty $beauty, array $filters): array
    {
        Log::info('뷰티 프로필 수정', [
            'beauty_id' => $beauty->id,
            'keys' => array_keys($filters),
        ]);

        $beauty = $this->query->update($beauty, $filters);

        return [
            'profile' => ProfileForAccountBeautyDto::fromModel($beauty)->toArray(),
        ];
    }
}
