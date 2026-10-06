<?php

namespace App\Domains\VideoExposure\Actions;

use App\Domains\VideoExposure\Queries\VideoExposureQuery;
use Illuminate\Support\Facades\DB;

final class VideoExposureClickAction
{
    public function __construct(private readonly VideoExposureQuery $query) {}

    public function execute(string $placement, ?int $categoryId, string $sourceKey): void
    {
        $scope = $this->query->scope($placement, $categoryId);
        abort_unless($this->query->entries($scope, now())->where('active', 1)->where('source_key', $sourceKey)->exists(), 404);
        DB::table('video_exposure_metrics')->upsert(
            [['position_key' => $scope['key'], 'source_key' => $sourceKey, 'click_count' => 1]],
            ['position_key', 'source_key'],
            ['click_count' => DB::raw('click_count + 1')],
        );
    }
}
