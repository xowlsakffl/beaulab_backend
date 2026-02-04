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
import React from 'react';
import { ChevronLeft, ChevronRight, RotateCw } from 'lucide-react';

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
};

const DEFAULT_HEADER_CELL =
    'px-5 py-3 font-semibold text-gray-600 text-left text-theme-xs dark:text-gray-300';

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
}: Props<T>) {
    const colCount = Math.max(1, columns.length);
    const current = meta?.current_page ?? 1;
    const last = meta?.last_page ?? 1;

    // (4) pagination: < 1 2 3 4 >
    const pages = React.useMemo(() => {
        if (!meta) return [];
        const total = meta.last_page;
        // 너무 많아지면 windowing 가능, 지금은 단순 버전
        return Array.from({ length: total }, (_, i) => i + 1);
    }, [meta]);

    return (
        <div className="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/[0.05] dark:bg-white/[0.03]">
            {(title || description || rightActions || onRefresh) && (
                <div className="flex items-center justify-between gap-3 px-4 py-4 sm:px-4">
                    {/* LEFT */}
                    <div className="flex items-center gap-2">
                        <div>
                            {title ? (
                                <div className="text-base font-semibold text-gray-800 dark:text-white/90">
                                    {title}
                                </div>
                            ) : null}
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

            <div className="max-w-full overflow-x-auto">
                <Table>
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

                        {pages.map((p) => {
                            const active = p === current;

                            return (
                                <button
                                    key={p}
                                    type="button"
                                    onClick={
                                        active ? undefined : () => onGoPage(p)
                                    }
                                    disabled={refreshing || active}
                                    aria-current={active ? 'page' : undefined}
                                    className={[
                                        'h-9 min-w-9 rounded-lg border px-3 text-sm font-medium',
                                        'border-gray-200 text-gray-800',
                                        'dark:border-white/[0.05] dark:text-white/90',

                                        active
                                            ? 'pointer-events-none cursor-default bg-brand-500 text-white' //
                                            : 'bg-transparent hover:bg-gray-50 dark:hover:bg-white/[0.06]',

                                        refreshing ? 'opacity-60' : '',
                                    ].join(' ')}
                                >
                                    {p}
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
