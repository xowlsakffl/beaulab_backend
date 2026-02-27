<?php

namespace App\Domains\VideoRequest\Actions\Partner;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\VideoRequest\Dto\Partner\VideoRequestForPartnerDetailDto;
use App\Domains\VideoRequest\Models\VideoRequest;
use App\Domains\VideoRequest\Queries\Partner\VideoRequestCancelForPartnerQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class VideoRequestCancelForPartnerAction
{
    public function __construct(private readonly VideoRequestCancelForPartnerQuery $query) {}

    public function execute(VideoRequest $videoRequest, array $payload): array
    {
        unset($payload);

        Gate::authorize('cancel', $videoRequest);

        if (! $videoRequest->isPending()) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, 'PENDING 상태일 때만 취소할 수 있습니다.');
        }

        $videoRequest = DB::transaction(fn () => $this->query->cancel($videoRequest));

        return [
            'video_request' => VideoRequestForPartnerDetailDto::fromModel($videoRequest)->toArray(),
        ];
    }
}
