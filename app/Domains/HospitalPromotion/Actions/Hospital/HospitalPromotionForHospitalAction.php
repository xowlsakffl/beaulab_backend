<?php

namespace App\Domains\HospitalPromotion\Actions\Hospital;

use App\Domains\HospitalPromotion\Dto\HospitalPromotionPublicDto;
use App\Domains\HospitalPromotion\Models\HospitalPromotion;
use App\Domains\HospitalPromotion\Queries\HospitalPromotionQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalPromotionForHospitalAction
{
    public function __construct(private readonly HospitalPromotionQuery $query) {}

    public function listing(): array
    {
        Gate::authorize('viewPublic', HospitalPromotion::class);
        $today = HospitalPromotion::today();
        $promotions = $this->query->listing(false)->publicCurrent($today)->orderBy('slot')->orderBy('id')->get();
        $result = [];
        foreach (HospitalPromotion::SIDES as $side) {
            $result[strtolower($side)] = $promotions->where('side', $side)->take(3)
                ->map(fn ($promotion) => HospitalPromotionPublicDto::fromModel($promotion, $today)->toArray())->values()->all();
        }

        return $result;
    }

    public function detail(int $id): array
    {
        Gate::authorize('viewPublic', HospitalPromotion::class);
        $today = HospitalPromotion::today();
        $promotion = HospitalPromotion::query()->publicCurrent($today)->with('banner')->findOrFail($id);

        return HospitalPromotionPublicDto::fromModel($promotion, $today, true)->toArray();
    }

    public function click(int $id): array
    {
        Gate::authorize('viewPublic', HospitalPromotion::class);
        // Base query avoids touching updated_at (the optimistic edit token).
        $count = HospitalPromotion::query()->whereKey($id)->publicCurrent(HospitalPromotion::today())->toBase()->increment('click_count');
        abort_if($count === 0, 404);

        return ['counted' => true];
    }
}
