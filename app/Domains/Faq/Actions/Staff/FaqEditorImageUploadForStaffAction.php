<?php

namespace App\Domains\Faq\Actions\Staff;

use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\Faq\Models\Faq;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

final class FaqEditorImageUploadForStaffAction
{
    public function __construct(
        private readonly MediaAttachDeleteAction $mediaAttachDeleteAction,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(UploadedFile $image, ?int $faqId = null): array
    {
        if ($faqId !== null) {
            $faq = Faq::query()->findOrFail($faqId);
            Gate::authorize('update', $faq);

            $media = $this->mediaAttachDeleteAction->attachOne(
                $faq,
                $image,
                'editor_images',
                'faq',
                'editor-images',
                false,
            );

            return [
                'faq_id' => (int) $faq->id,
                'media_id' => $media?->id,
                'disk' => $media?->disk ?? 'public',
                'path' => $media?->path,
                'url' => $media ? Storage::disk((string) $media->disk)->url((string) $media->path) : null,
                'is_temporary' => false,
            ];
        }

        Gate::authorize('create', Faq::class);

        $disk = 'public';
        $path = Storage::disk($disk)->putFile('faq/editor-images/temp', $image);

        return [
            'faq_id' => null,
            'media_id' => null,
            'disk' => $disk,
            'path' => $path,
            'url' => Storage::disk($disk)->url($path),
            'is_temporary' => true,
        ];
    }
}
