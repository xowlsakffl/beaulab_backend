<?php

namespace App\Domains\Talk\Actions\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use App\Domains\Talk\Queries\Staff\TalkListForStaffQuery;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class TalkExcelDownloadForStaffAction
{
    private const CHUNK_SIZE = 300;

    public function __construct(
        private readonly TalkListForStaffQuery $query,
    ) {}

    public function execute(array $filters): StreamedResponse
    {
        Gate::authorize('viewAny', Talk::class);

        $fileName = 'talks_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($filters): void {
            $output = fopen('php://output', 'wb');

            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'id',
                '작성일',
                '카테고리',
                '닉네임',
                '제목',
                '내용',
                '댓글(5개까지)',
                '노출상태',
                '게시상태',
            ]);

            $this->query->chunkForExport($filters, self::CHUNK_SIZE, function (Collection $talks) use ($output): void {
                $commentsByTalkId = $this->query->commentsForTalkIds(
                    $talks->pluck('id')->map(static fn ($id): int => (int) $id)->all(),
                    5,
                );

                foreach ($talks as $talk) {
                    if (! $talk instanceof Talk) {
                        continue;
                    }

                    fputcsv($output, $this->row($talk, $commentsByTalkId));
                }

                fflush($output);
            });

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param Collection<int, Collection<int, TalkComment>> $commentsByTalkId
     * @return array<int, string|int|null>
     */
    private function row(Talk $talk, Collection $commentsByTalkId): array
    {
        return [
            (int) $talk->id,
            $talk->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
            $this->categories($talk),
            $this->nickname($talk->author),
            (string) $talk->title,
            (string) $talk->content,
            $this->comments($commentsByTalkId->get((int) $talk->id, collect())),
            (string) $talk->status,
            (string) $talk->post_status,
        ];
    }

    private function categories(Talk $talk): string
    {
        if (! $talk->relationLoaded('categories')) {
            return '';
        }

        return $talk->categories
            ->map(static function (Category $category): string {
                $attributes = $category->getAttributes();
                $fullPath = trim((string) ($attributes['full_path'] ?? ''));

                return $fullPath !== '' ? $fullPath : (string) $category->name;
            })
            ->filter(static fn (string $category): bool => $category !== '')
            ->unique()
            ->implode(' / ');
    }

    private function comments(Collection $comments): string
    {
        return $comments
            ->map(fn (TalkComment $comment): string => $this->nickname($comment->author) . ',' . (string) $comment->content)
            ->implode(' / ');
    }

    private function nickname(mixed $author): string
    {
        if (! $author) {
            return '';
        }

        $attributes = method_exists($author, 'getAttributes') ? $author->getAttributes() : [];
        $nickname = trim((string) ($attributes['nickname'] ?? ''));

        if ($nickname !== '') {
            return $nickname;
        }

        return trim((string) ($attributes['name'] ?? ''));
    }
}
