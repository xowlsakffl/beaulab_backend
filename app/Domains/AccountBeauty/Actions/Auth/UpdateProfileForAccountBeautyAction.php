<?php

namespace App\Domains\AccountBeauty\Actions\Auth;

use App\Domains\AccountBeauty\Dto\Auth\ProfileForAccountBeautyDto;
use App\Domains\AccountBeauty\Models\AccountBeauty;
use App\Domains\AccountBeauty\Queries\Auth\UpdateProfileForAccountBeautyQuery;
use Illuminate\Support\Facades\Log;

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
