import AppLayout from '@/layouts/admin/app-layout';
import { dashboard } from '@/routes/admin';
import hospitals from '@/routes/admin/hospitals';
import type { BreadcrumbItem } from '@/types';
import { type ReactNode, useMemo } from 'react';
import { Download, Filter, Plus } from 'lucide-react';

import DataTable, {
    type DataTableColumn,
    type DataTableMeta,
} from '@/components/data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Link, usePage } from '@inertiajs/react';

import FilterBar, { type FilterField } from '@/components/filter-bar';
import { useInertiaQueryState } from '@/hooks/use-inertia-query-state';
import { useLocalStorageState } from '@/hooks/use-local-storage-state';
import { cn } from '@/lib/utils';

/* =====================
 * Types
 * ===================== */
type AllowStatus = 'PENDING' | 'APPROVED' | 'REJECTED';
type Status = 'ACTIVE' | 'SUSPENDED' | 'WITHDRAWN';

interface HospitalRow {
    id: number;
    name: string;
    address: string | null;
    tel: string | null;
    view_count: number;
    allow_status: AllowStatus;
    status: Status;
}

export type HospitalFilters = {
    q: string | null;
    status: Status | null;
    allow_status: AllowStatus | null;

    sort: string | null;
    direction: 'asc' | 'desc' | null;

    page: number;
    per_page: number;
};

interface PageProps {
    items: HospitalRow[];
    meta: DataTableMeta | null;
    filters: Partial<HospitalFilters>;
}

/* =====================
 * Constants
 * ===================== */
const breadcrumbs: BreadcrumbItem[] = [
    { title: '홈', href: dashboard().url },
    { title: '병원 관리', href: hospitals.indexHospitalPageForStaff().url },
    { title: '병원 목록', href: hospitals.indexHospitalPageForStaff().url },
];

const statusLabel: Record<
    Status,
    { label: string; color: 'success' | 'warning' | 'error' }
> = {
    ACTIVE: { label: '정상', color: 'success' },
    SUSPENDED: { label: '정지', color: 'warning' },
    WITHDRAWN: { label: '탈퇴', color: 'error' },
};

const allowLabel: Record<
    AllowStatus,
    { label: string; color: 'success' | 'warning' | 'error' }
> = {
    PENDING: { label: '검수신청', color: 'warning' },
    APPROVED: { label: '검수완료', color: 'success' },
    REJECTED: { label: '검수반려', color: 'error' },
};

const DEFAULT_FILTERS: HospitalFilters = {
    q: null,
    status: null,
    allow_status: null,
    sort: null,
    direction: null,
    page: 1,
    per_page: 15,
};

function parseStatus(v: string | null): Status | null {
    if (v === 'ACTIVE' || v === 'SUSPENDED' || v === 'WITHDRAWN') return v;
    return null;
}
function parseAllowStatus(v: string | null): AllowStatus | null {
    if (v === 'PENDING' || v === 'APPROVED' || v === 'REJECTED') return v;
    return null;
}
function parseDirection(v: string | null): 'asc' | 'desc' | null {
    if (v === 'asc' || v === 'desc') return v;
    return null;
}
function parseNumber(v: string | null, fallback: number): number {
    if (!v) return fallback;
    const n = Number(v);
    return Number.isFinite(n) && n > 0 ? n : fallback;
}

/* =====================
 * Component
 * ===================== */
function IndexHospitals() {
    const [filterCollapsed, setFilterCollapsed] = useLocalStorageState<boolean>(
        'admin.hospitals.filterCollapsed',
        false,
    );
    const { items, meta, filters: serverFilters } = usePage<PageProps>().props;

    const query = useInertiaQueryState<HospitalFilters>({
        url: hospitals.indexHospitalPageForStaff().url,
        only: ['items', 'meta', 'filters'],
        parse: (params) => ({
            q: params.get('q'),
            status: parseStatus(params.get('status')),
            allow_status: parseAllowStatus(params.get('allow_status')),
            sort: params.get('sort'),
            direction: parseDirection(params.get('direction')),
            page: parseNumber(params.get('page'), DEFAULT_FILTERS.page),
            per_page: parseNumber(
                params.get('per_page'),
                DEFAULT_FILTERS.per_page,
            ),
        }),
        serialize: (f) => ({
            q: f.q,
            status: f.status,
            allow_status: f.allow_status,
            sort: f.sort,
            direction: f.direction,
            page: f.page,
            per_page: f.per_page,
        }),
        clean: (q) => {
            const out: Record<string, any> = {};
            for (const [k, v] of Object.entries(q)) {
                if (v === null || v === undefined) continue;
                if (typeof v === 'string' && v.trim() === '') continue;
                if (k === 'page' && Number(v) === DEFAULT_FILTERS.page)
                    continue;
                if (k === 'per_page' && Number(v) === DEFAULT_FILTERS.per_page)
                    continue;
                out[k] = v;
            }
            return out;
        },
    });

    const refreshing = query.refreshing;

    const filters: HospitalFilters = useMemo(() => {
        return {
            ...DEFAULT_FILTERS,
            ...serverFilters,
            page:
                serverFilters?.page ??
                meta?.current_page ??
                DEFAULT_FILTERS.page,
            per_page:
                serverFilters?.per_page ??
                meta?.per_page ??
                DEFAULT_FILTERS.per_page,
        };
    }, [serverFilters, meta?.current_page, meta?.per_page]);

    const columns = useMemo<DataTableColumn<HospitalRow>[]>(() => {
        return [
            {
                key: 'id',
                header: '#',
                render: (h) => (
                    <span className="font-mono text-xs">{h.id}</span>
                ),
            },
            {
                key: 'name',
                header: '병원명',
                render: (h) => (
                    <div className="flex flex-col">
                        <span className="font-semibold">{h.name}</span>
                        <span className="text-muted-foreground text-xs">
                            {h.address || '-'}
                        </span>
                    </div>
                ),
            },
            { key: 'tel', header: '연락처', render: (h) => h.tel || '-' },
            {
                key: 'view_count',
                header: '조회수',
                render: (h) => h.view_count.toLocaleString(),
            },
            {
                key: 'allow_status',
                header: '검수',
                render: (h) => (
                    <Badge
                        variant="light"
                        color={allowLabel[h.allow_status].color}
                        size="sm"
                    >
                        <span className="text-xs">
                            {allowLabel[h.allow_status].label}
                        </span>
                    </Badge>
                ),
            },
            {
                key: 'status',
                header: '상태',
                render: (h) => (
                    <Badge
                        variant="light"
                        color={statusLabel[h.status].color}
                        size="sm"
                    >
                        <span className="text-xs">
                            {statusLabel[h.status].label}
                        </span>
                    </Badge>
                ),
            },
        ];
    }, []);

    const fields = useMemo<FilterField<HospitalFilters>[]>(() => {
        return [
            {
                type: 'text',
                key: 'q',
                placeholder: '병원명/주소 검색',
                debounceMs: 300,
                normalize: (v) => v,
                className: 'w-full lg:w-72',
            },
            {
                type: 'select',
                key: 'status',
                label: '상태',
                placeholder: '전체',
                nullValue: null,
                options: [
                    { label: '정상', value: 'ACTIVE' },
                    { label: '정지', value: 'SUSPENDED' },
                    { label: '탈퇴', value: 'WITHDRAWN' },
                ],
                className: 'w-full lg:w-44',
            },
            {
                type: 'select',
                key: 'allow_status',
                label: '검수',
                placeholder: '전체',
                nullValue: null,
                options: [
                    { label: '검수신청', value: 'PENDING' },
                    { label: '검수완료', value: 'APPROVED' },
                    { label: '검수반려', value: 'REJECTED' },
                ],
                className: 'w-full lg:w-44',
            },
        ];
    }, []);

    return (
        <>
            {/* 상단 오른쪽 액션 */}
            <div className="flex justify-end gap-2 pb-4">
                <Button
                    type="button"
                    variant={filterCollapsed ? 'default' : 'outline'}
                    className={cn(
                        // 공통
                        'text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200',

                        // 토글 상태
                        filterCollapsed &&
                            'bg-brand-500 text-white hover:bg-brand-600 dark:bg-brand-500',
                    )}
                    onClick={() => setFilterCollapsed((v) => !v)}
                >
                    <Filter />
                    필터
                </Button>

                <Button
                    type="button"
                    variant="outline"
                    className="text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200"
                >
                    <Download />
                    다운로드
                </Button>

                <Button
                    className="bg-brand-500 text-white hover:bg-brand-600"
                    asChild
                    disabled={refreshing}
                >
                    <Link href={hospitals.createHospitalForStaff().url}>
                        <Plus />
                        신규 병원 등록
                    </Link>
                </Button>
            </div>

            <Card className="border-none shadow-sm">
                <CardContent className="px-6">
                    <div
                        className={[
                            'grid transition-[grid-template-rows,opacity,margin] duration-300 ease-out',
                            filterCollapsed
                                ? 'mb-0 grid-rows-[0fr] opacity-0'
                                : 'mb-4 grid-rows-[1fr] opacity-100',
                        ].join(' ')}
                    >
                        <div className="overflow-hidden">
                            <FilterBar
                                value={filters}
                                fields={fields}
                                disabled={false}
                                onChange={(next: Partial<HospitalFilters>) =>
                                    query.update(next, { resetPage: true })
                                }
                                onReset={() =>
                                    query.update(
                                        {
                                            q: null,
                                            status: null,
                                            allow_status: null,
                                            sort: null,
                                            direction: null,
                                            page: 1,
                                            per_page: DEFAULT_FILTERS.per_page,
                                        },
                                        { resetPage: true },
                                    )
                                }
                            />
                        </div>
                    </div>

                    <DataTable
                        description={
                            meta
                                ? `전체 ${meta.total.toLocaleString()}개`
                                : '목록을 불러오는 중입니다.'
                        }
                        columns={columns}
                        rows={items ?? []}
                        getRowKey={(h) => h.id}
                        meta={meta}
                        refreshing={refreshing}
                        paginationWindow={9}
                        onGoPage={(page) => query.update({ page })}
                        onRefresh={() => query.reload()}
                        onRowClick={(h) =>
                            query.visit(
                                hospitals.editHospitalForStaff(h.id).url,
                            )
                        }
                    />
                </CardContent>
            </Card>
        </>
    );
}

IndexHospitals.layout = (page: ReactNode) => (
    <AppLayout breadcrumbs={breadcrumbs}>{page}</AppLayout>
);

export default IndexHospitals;
