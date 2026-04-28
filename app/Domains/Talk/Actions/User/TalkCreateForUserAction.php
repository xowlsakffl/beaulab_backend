<?php

namespace App\Domains\Talk\Actions\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Talk\Dto\User\TalkForUserDetailDto;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkPoll;
use App\Domains\Talk\Queries\User\TalkCreateForUserQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class TalkCreateForUserAction
{
    public function __construct(
        private readonly TalkCreateForUserQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
    ) {}

    public function execute(AccountUser $user, array $payload): array
    {
        $normalized = $payload;
        $normalized['author_id'] = (int) $user->id;
        $normalized['category_id'] = $this->resolveCategoryId($normalized['category_code'] ?? null);

        $talk = DB::transaction(function () use ($normalized): Talk {
            $talk = $this->query->create($normalized);

            $this->syncCategory($talk, $normalized['category_id'] ?? null);
            $this->attachImages($talk, $normalized['images'] ?? []);
            $this->createPoll($talk, $normalized['poll'] ?? null);

            return $talk->fresh([
                'author',
                'categories',
                'images',
                'poll.options',
            ]);
        });

        return [
            'talk' => TalkForUserDetailDto::fromModel($talk)->toArray(),
        ];
    }

    private function syncCategory(Talk $talk, mixed $categoryId): void
    {
        $categoryId = (int) $categoryId;

        if ($categoryId <= 0) {
            return;
        }

        $talk->categories()->sync([
            $categoryId => ['is_primary' => true],
        ]);
    }

    private function resolveCategoryId(mixed $categoryCode): ?int
    {
        if (! is_string($categoryCode) || trim($categoryCode) === '') {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '토크 카테고리를 확인해 주세요.');
        }

        $categoryId = Category::query()
            ->where('domain', Talk::CATEGORY_DOMAIN)
            ->where('status', Category::STATUS_ACTIVE)
            ->where('code', trim($categoryCode))
            ->value('id');

        $categoryId = is_numeric($categoryId) ? (int) $categoryId : 0;

        if ($categoryId <= 0) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '토크 카테고리를 확인해 주세요.');
        }

        return $categoryId;
    }

    /**
     * @param array<int, UploadedFile> $images
     */
    private function attachImages(Talk $talk, array $images): void
    {
        if ($images === []) {
            return;
        }

        $this->mediaAttachAction->attachMany(
            $talk,
            $images,
            'images',
            'talk',
            'images',
        );
    }

    private function createPoll(Talk $talk, mixed $pollPayload): void
    {
        if (! is_array($pollPayload)) {
            return;
        }

        $poll = $talk->poll()->create([
            'allow_multiple' => (bool) ($pollPayload['allow_multiple'] ?? false),
        ]);

        if (! $poll instanceof TalkPoll) {
            return;
        }

        collect($pollPayload['options'] ?? [])
            ->values()
            ->each(function (string $content, int $index) use ($poll): void {
                $poll->options()->create([
                    'content' => $content,
                    'sort_order' => $index + 1,
                    'vote_count' => 0,
                ]);
            });
    }
}
