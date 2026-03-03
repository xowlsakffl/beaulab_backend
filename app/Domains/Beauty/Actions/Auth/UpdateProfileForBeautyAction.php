<?php

namespace App\Domains\Beauty\Actions\Auth;

use App\Domains\Beauty\Dto\Auth\ProfileForBeautyDto;
use App\Domains\Beauty\Models\AccountBeauty;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class UpdateProfileForBeautyAction
{
    /**
     * @param array{name?:string,email?:string} $filters
     * @return array{profile: array}
     */
    public function execute(AccountBeauty $beauty, array $filters): array
    {
        Log::info('파트너 프로필 수정', [
            'beauty_id' => $beauty->id,
            'keys' => array_keys($filters),
        ]);

        $beauty = DB::transaction(function () use ($beauty, $filters) {
            $beauty->fill($filters)->save();

            return $beauty->fresh();
        });

        return [
            'profile' => ProfileForBeautyDto::fromModel($beauty)->toArray(),
        ];
    }
}
