import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { ChevronLeft, ChevronRight, RotateCw } from 'lucide-react';
import React from 'react';

export type DataTableColumn<T> = {
    key: string;
    header: React.ReactNode;

    headerClassName?: string;
    cellClassName?: string;

    render: (row: T) => React.ReactNode;
};

export type DataTableMeta = {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
};

type Props<T> = {
    title?: React.ReactNode;
    description?: React.ReactNode;

    /** 오른쪽 슬롯(생성 버튼, 새로고침 등) */
    rightActions?: React.ReactNode;

    columns: DataTableColumn<T>[];
    rows: T[];
    getRowKey: (row: T) => string | number;

    loading?: boolean;
    error?: string | null;
    emptyText?: string;
    skeletonRows?: number;

    meta?: DataTableMeta | null;

    /** 숫자 페이지로 이동 */
    onGoPage?: (page: number) => void;

    /** 새로고침 */
    onRefresh?: () => void;
    refreshing?: boolean;

    onRowClick?: (row: T) => void;

    /**
     * pagination windowing 크기(표시할 페이지 버튼 개수)
     * - 페이지가 많을 때 전부 렌더링하면 느려져서 windowing 권장
     * - 기본 7
     */
    paginationWindow?: number;
};

const DEFAULT_HEADER_CELL =
    'px-5 py-3 font-semibold text-gray-600 text-left text-theme-xs dark:text-gray-300';

type PageItem =
    | { type: 'page'; page: number; active: boolean }
    | { type: 'ellipsis'; key: string };

function buildPageItems(
    current: number,
    last: number,
    windowSize: number,
): PageItem[] {
    if (last <= 1) return [{ type: 'page', page: 1, active: true }];

    const safeWindow = Math.max(5, windowSize); // 최소 5 (1 ... x ... last 구조 고려)
    const half = Math.floor(safeWindow / 2);

    let start = Math.max(1, current - half);
    let end = Math.min(last, current + half);

    // window 크기 유지 보정
    const span = end - start + 1;
    if (span < safeWindow) {
        const shortage = safeWindow - span;
        start = Math.max(1, start - shortage);
        end = Math.min(last, end + shortage);
    }

    // 그래도 모자라면 반대 방향으로 다시 보정
    const span2 = end - start + 1;
    if (span2 < safeWindow) {
        if (start === 1) end = Math.min(last, start + safeWindow - 1);
        else if (end === last) start = Math.max(1, end - safeWindow + 1);
    }

    const items: PageItem[] = [];

    // 항상 1 페이지는 보여주고, start가 2 이상이면 ... 처리
    if (start > 1) {
        items.push({ type: 'page', page: 1, active: current === 1 });

        if (start > 2) {
            items.push({ type: 'ellipsis', key: 'left-ellipsis' });
        }
    }

    for (let p = start; p <= end; p++) {
        // start가 1이면 위에서 1을 넣지 않았으므로 여기서 들어감
        // start가 >1이면 1은 이미 넣었으니 중복 방지
        if (p === 1 && start > 1) continue;
        if (p === last && end < last) continue;

        items.push({ type: 'page', page: p, active: p === current });
    }

    // 항상 last 페이지는 보여주고, end가 last-1 이하이면 ... 처리
    if (end < last) {
        if (end < last - 1) {
            items.push({ type: 'ellipsis', key: 'right-ellipsis' });
        }
        items.push({ type: 'page', page: last, active: current === last });
    }

    // 정렬 보장(혹시 중복/역전 방지)
    const normalized: PageItem[] = [];
    const seenPage = new Set<number>();
    for (const it of items) {
        if (it.type === 'page') {
            if (seenPage.has(it.page)) continue;
            seenPage.add(it.page);
        }
        normalized.push(it);
    }

    return normalized;
}

export default function DataTable<T>({
    title,
    description,
    rightActions,

    columns,
    rows,
    getRowKey,

    loading = false,
    error = null,
    emptyText = '데이터가 없습니다.',
    skeletonRows = 6,

    meta = null,
    onGoPage,

    onRefresh,
    refreshing = false,

    onRowClick,

    paginationWindow = 7,
}: Props<T>) {
    const colCount = Math.max(1, columns.length);
    const current = meta?.current_page ?? 1;
    const last = meta?.last_page ?? 1;

    // pagination items: 1 ... 4 5 6 ... 99
    const pageItems = React.useMemo(() => {
        if (!meta) return [];
        return buildPageItems(current, meta.last_page, paginationWindow);
    }, [meta, current, paginationWindow]);

    return (
        <div className="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/[0.05] dark:bg-white/[0.03]">
            {(title || description || rightActions || onRefresh) && (
                <div className="flex items-center justify-between gap-3 px-4 py-4 sm:px-4">
                    {/* LEFT */}
                    <div className="flex items-center gap-2">
                        <div>
                            {description ? (
                                <div className="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                                    {description}
                                </div>
                            ) : null}
                        </div>
                    </div>

                    {/* RIGHT */}
                    <div className="flex items-center gap-3">
                        {onRefresh ? (
                            <button
                                type="button"
                                onClick={onRefresh}
                                disabled={refreshing}
                                className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50 disabled:opacity-60 dark:border-white/[0.05] dark:text-white/90 dark:hover:bg-white/[0.06]"
                                title="새로고침"
                            >
                                {refreshing ? (
                                    <Spinner className="h-4 w-4" />
                                ) : (
                                    <RotateCw className="h-4 w-4" />
                                )}
                            </button>
                        ) : null}

                        {rightActions}
                    </div>
                </div>
            )}

            <div
                className="max-w-full overflow-x-auto"
                style={{ WebkitOverflowScrolling: 'touch' }}
            >
                <Table className="w-max min-w-full">
                    <TableHeader className="border-b border-gray-100 dark:border-white/[0.05]">
                        <TableRow>
                            {columns.map((c) => (
                                <TableCell
                                    key={c.key}
                                    isHeader
                                    className={
                                        c.headerClassName ?? DEFAULT_HEADER_CELL
                                    }
                                >
                                    {c.header}
                                </TableCell>
                            ))}
                        </TableRow>
                    </TableHeader>

                    <TableBody className="divide-y divide-gray-100 dark:divide-white/[0.05]">
                        {!loading && error ? (
                            <TableRow>
                                <TableCell
                                    className="px-5 py-6 text-start text-theme-sm text-rose-600"
                                    colSpan={colCount}
                                >
                                    {error}
                                </TableCell>
                            </TableRow>
                        ) : null}

                        {loading
                            ? Array.from({ length: skeletonRows }).map(
                                  (_, rIdx) => (
                                      <TableRow key={`sk-${rIdx}`}>
                                          {columns.map((c, cIdx) => (
                                              <TableCell
                                                  key={`${c.key}-${cIdx}`}
                                                  className={
                                                      c.cellClassName ??
                                                      'px-5 py-4 text-start sm:px-6'
                                                  }
                                              >
                                                  <Skeleton className="h-4 w-[70%]" />
                                              </TableCell>
                                          ))}
                                      </TableRow>
                                  ),
                              )
                            : null}

                        {!loading && !error && rows.length === 0 ? (
                            <TableRow>
                                <TableCell
                                    className="px-5 py-10 text-center text-theme-sm text-gray-500 dark:text-gray-400"
                                    colSpan={colCount}
                                >
                                    {emptyText}
                                </TableCell>
                            </TableRow>
                        ) : null}

                        {!loading && !error
                            ? rows.map((row) => (
                                  <TableRow
                                      key={getRowKey(row)}
                                      className={
                                          onRowClick
                                              ? 'cursor-pointer hover:bg-gray-50 dark:hover:bg-white/[0.03]'
                                              : undefined
                                      }
                                      onClick={() => onRowClick?.(row)}
                                  >
                                      {columns.map((c) => (
                                          <TableCell
                                              key={c.key}
                                              className={
                                                  c.cellClassName ??
                                                  'px-5 py-4 text-start sm:px-6 dark:text-gray-200'
                                              }
                                          >
                                              {c.render(row)}
                                          </TableCell>
                                      ))}
                                  </TableRow>
                              ))
                            : null}
                    </TableBody>
                </Table>
            </div>

            {meta && onGoPage ? (
                <div className="flex items-center justify-end px-5 py-4 sm:px-6">
                    <div className="flex items-center gap-2">
                        {current > 1 ? (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => onGoPage(current - 1)}
                                disabled={refreshing}
                                className="h-9 min-w-9 rounded-lg border border-gray-200 px-3 text-sm font-medium text-gray-800 hover:bg-gray-50 dark:border-white/[0.05] dark:text-white/90 dark:hover:bg-white/[0.06]"
                            >
                                <ChevronLeft />
                            </Button>
                        ) : null}

                        {pageItems.map((it) => {
                            if (it.type === 'ellipsis') {
                                return (
                                    <span
                                        key={it.key}
                                        className="flex h-9 min-w-9 items-center justify-center rounded-lg border border-gray-200 px-3 text-sm font-medium text-gray-500 select-none dark:border-white/[0.05] dark:text-gray-400"
                                        aria-hidden
                                    >
                                        …
                                    </span>
                                );
                            }

                            const active = it.active;

                            return (
                                <button
                                    key={it.page}
                                    type="button"
                                    onClick={
                                        active
                                            ? undefined
                                            : () => onGoPage(it.page)
                                    }
                                    disabled={refreshing || active}
                                    aria-current={active ? 'page' : undefined}
                                    className={[
                                        'h-9 min-w-9 rounded-lg border px-3 text-sm font-medium',
                                        'border-gray-200 text-gray-800',
                                        'dark:border-white/[0.05] dark:text-white/90',

                                        active
                                            ? 'pointer-events-none cursor-default bg-brand-500 text-white'
                                            : 'bg-transparent hover:bg-gray-50 dark:hover:bg-white/[0.06]',

                                        refreshing ? 'opacity-60' : '',
                                    ].join(' ')}
                                >
                                    {it.page}
                                </button>
                            );
                        })}

                        {current < last ? (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => onGoPage(current + 1)}
                                disabled={refreshing}
                                className="h-9 min-w-9 rounded-lg border border-gray-200 px-3 text-sm font-medium text-gray-800 hover:bg-gray-50 dark:border-white/[0.05] dark:text-white/90 dark:hover:bg-white/[0.06]"
                            >
                                <ChevronRight />
                            </Button>
                        ) : null}
                    </div>
                </div>
            ) : null}
        </div>
    );
}
