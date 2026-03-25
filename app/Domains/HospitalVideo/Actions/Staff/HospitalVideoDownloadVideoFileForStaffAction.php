<?php

namespace App\Domains\HospitalVideo\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class HospitalVideoDownloadVideoFileForStaffAction
{
    public function execute(HospitalVideo $video): StreamedResponse
    {
        Gate::authorize('view', $video);

        $media = $video->videoFileMedia()->first();

        if (! $media) {
            throw new CustomException(ErrorCode::NOT_FOUND, '원본 동영상 파일이 없습니다.');
        }

        if (! Storage::disk($media->disk)->exists($media->path)) {
            throw new CustomException(ErrorCode::NOT_FOUND, '원본 동영상 파일을 찾을 수 없습니다.');
        }

        $fileName = basename((string) $media->path) ?: sprintf('hospital-video-%d', $video->id);

        return Storage::disk($media->disk)->download($media->path, $fileName);
    }
}
