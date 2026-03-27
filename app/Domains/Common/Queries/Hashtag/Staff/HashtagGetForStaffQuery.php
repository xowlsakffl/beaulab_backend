<?php

namespace App\Domains\Common\Queries\Hashtag\Staff;

use App\Domains\Common\Models\Hashtag\Hashtag;
use Illuminate\Support\Facades\DB;

final class HashtagGetForStaffQuery
{
    public function get(Hashtag $hashtag): Hashtag
    {
        $query = Hashtag::query()
            ->select([
                'id',
                'name',
                'normalized_name',
                'created_at',
                'updated_at',
            ])
            ->selectSub(
                DB::table('hashtaggables')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('hashtaggables.hashtag_id', 'hashtags.id'),
                'assignment_count',
            );

        if (Hashtag::supportsUsageCount()) {
            $query->addSelect('usage_count');
        }

        if (Hashtag::supportsStatus()) {
            $query->addSelect('status');
        }

        return $query->findOrFail($hashtag->id);
    }
}
