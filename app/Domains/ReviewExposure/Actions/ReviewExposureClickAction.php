<?php

namespace App\Domains\ReviewExposure\Actions;

use App\Domains\ReviewExposure\Queries\ReviewExposureQuery;
use Illuminate\Support\Facades\DB;

final class ReviewExposureClickAction
{
    public function __construct(private readonly ReviewExposureQuery $query) {}

    public function execute(string $placement, ?int $categoryId, string $sourceKey): void
    {
        $scope = $this->query->scope($placement, $categoryId);
        abort_unless($this->query->entries($scope, now())->where('active', 1)->where('source_key', $sourceKey)->exists(), 404);
        DB::table('review_exposure_metrics')->upsert(
            [['position_key' => $scope['key'], 'source_key' => $sourceKey, 'click_count' => 1]],
            ['position_key', 'source_key'],
            ['click_count' => DB::raw('click_count + 1')],
        );
    }
}
