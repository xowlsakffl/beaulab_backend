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

        $fileName = '토크_' . now()->format('Ymd_His') . '.xls';

        return response()->streamDownload(function () use ($filters): void {
            $output = fopen('php://output', 'wb');

            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fwrite($output, $this->workbookStart());
            $this->writeRow($output, [
                'id',
                '작성일',
                '토크유형',
                '닉네임',
                '제목',
                '내용',
                '댓글(5개까지)',
            ], 'Header');

            $this->query->chunkForExport($filters, self::CHUNK_SIZE, function (Collection $talks) use ($output): void {
                $commentsByTalkId = $this->query->commentsForTalkIds(
                    $talks->pluck('id')->map(static fn ($id): int => (int) $id)->all(),
                    5,
                );

                foreach ($talks as $talk) {
                    if (! $talk instanceof Talk) {
                        continue;
                    }

                    $this->writeRow($output, $this->row($talk, $commentsByTalkId));
                }

                fflush($output);
            });

            fwrite($output, $this->workbookEnd());
            fclose($output);
        }, $fileName, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
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
            ->map(fn (TalkComment $comment): string => $this->nickname($comment->author) . ': ' . (string) $comment->content)
            ->implode("\n");
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

    /**
     * @param resource $output
     * @param array<int, string|int|null> $values
     */
    private function writeRow($output, array $values, string $style = 'Text'): void
    {
        fwrite($output, '<Row ss:AutoFitHeight="1">');

        foreach ($values as $value) {
            fwrite($output, $this->cell($value, $style));
        }

        fwrite($output, "</Row>\n");
    }

    private function cell(string|int|null $value, string $style): string
    {
        return sprintf(
            '<Cell ss:StyleID="%s"><Data ss:Type="String">%s</Data></Cell>',
            $style,
            $this->xml((string) $value),
        );
    }

    private function xml(string $value): string
    {
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? '';
        $value = str_replace(["\r\n", "\r"], "\n", $value);

        return str_replace(
            "\n",
            '&#10;',
            htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8'),
        );
    }

    private function workbookStart(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:o="urn:schemas-microsoft-com:office:office"
    xmlns:x="urn:schemas-microsoft-com:office:excel"
    xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:html="http://www.w3.org/TR/REC-html40">
<Styles>
    <Style ss:ID="Header">
        <Font ss:Bold="1"/>
        <Interior ss:Color="#D9EAF7" ss:Pattern="Solid"/>
        <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
    </Style>
    <Style ss:ID="Text">
        <Alignment ss:Vertical="Top" ss:WrapText="1"/>
    </Style>
</Styles>
<Worksheet ss:Name="Talks">
<Table>
    <Column ss:Width="55"/>
    <Column ss:Width="125"/>
    <Column ss:Width="180"/>
    <Column ss:Width="130"/>
    <Column ss:Width="240"/>
    <Column ss:Width="520"/>
    <Column ss:Width="640"/>

XML;
    }

    private function workbookEnd(): string
    {
        return <<<'XML'
</Table>
<WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
    <FreezePanes/>
    <FrozenNoSplit/>
    <SplitHorizontal>1</SplitHorizontal>
    <TopRowBottomPane>1</TopRowBottomPane>
    <ActivePane>2</ActivePane>
</WorksheetOptions>
</Worksheet>
</Workbook>
XML;
    }
}
