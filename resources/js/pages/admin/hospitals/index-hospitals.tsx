import AppLayout from '@/layouts/admin/app-layout';
import { dashboard } from '@/routes/admin';
import hospitals from '@/routes/admin/hospitals';
import type { BreadcrumbItem } from '@/types';
import type { ReactNode } from 'react';
import { useEffect, useMemo, useRef, useState } from 'react';

import DataTable, {
    type DataTableColumn,
    type DataTableMeta,
} from '@/components/data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';

type AllowStatus = 'PENDING' | 'APPROVED' | 'REJECTED';
type Status = 'ACTIVE' | 'SUSPENDED' | 'WITHDRAWN';

type HospitalRow = {
    id: number;
    name: string;
    address: string | null;
    tel: string | null;
    view_count: number;
    allow_status: AllowStatus;
    status: Status;
    created_at: string;
    updated_at: string;
};

type ApiResponse<T> = {
    success: boolean;
    data: T;
    meta?: DataTableMeta;
    traceId?: string | null;
    error?: { code: string; message: string };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: '홈', href: dashboard().url },
    { title: '병원 관리', href: hospitals.indexHospitalPageForStaff().url },
    { title: '병원 목록', href: hospitals.indexHospitalPageForStaff().url },
];

const statusLabel: Record<Status, string> = {
    ACTIVE: '정상',
    SUSPENDED: '정지',
    WITHDRAWN: '탈퇴',
};

const allowLabel: Record<AllowStatus, string> = {
    PENDING: '검수신청',
    APPROVED: '검수완료',
    REJECTED: '검수반려',
};

function IndexHospitals() {
    const [items, setItems] = useState<HospitalRow[]>([]);
    const [meta, setMeta] = useState<DataTableMeta | null>(null);

    // 최초 로딩만 skeleton, 이후엔 refreshing으로만
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [errMsg, setErrMsg] = useState<string | null>(null);

    const [page, setPage] = useState(1);
    const [perPage] = useState(15);

    const abortRef = useRef<AbortController | null>(null);
    const firstLoadRef = useRef(true);

    // queryString 먼저 만들어야 cacheKey가 안전
    const queryString = useMemo(() => {
        const p = new URLSearchParams();
        p.set('page', String(page));
        p.set('per_page', String(perPage));
        p.set('sort', 'id');
        p.set('direction', 'desc');
        return p.toString();
    }, [page, perPage]);

    const cacheKey = useMemo(() => `hospitals:list:${queryString}`, [queryString]);

    // (5) 존재하지 않는 페이지 방지: clamp
    function goPage(nextPage: number) {
        const last = meta?.last_page ?? 1;
        const clamped = Math.min(Math.max(1, nextPage), last);
        setPage(clamped);
    }

    async function load(mode: 'initial' | 'refresh' = 'initial') {
        abortRef.current?.abort();
        const ac = new AbortController();
        abortRef.current = ac;

        if (mode === 'initial') setLoading(true);
        else setRefreshing(true);

        setErrMsg(null);

        try {
            const res = await fetch(`/admin/api/hospitals?${queryString}`, {
                headers: { Accept: 'application/json' },
                credentials: 'include',
                signal: ac.signal,
            });

            const json = (await res.json()) as ApiResponse<HospitalRow[]>;
            if (!res.ok || !json.success) {
                throw new Error(json?.error?.message || `HTTP ${res.status}`);
            }

            setItems(json.data ?? []);
            setMeta(json.meta ?? null);

            // (1) 메뉴 토글/이동으로 리마운트되어도 유지되게 캐시 저장
            sessionStorage.setItem(
                cacheKey,
                JSON.stringify({ items: json.data ?? [], meta: json.meta ?? null }),
            );
        } catch (e: any) {
            if (e?.name === 'AbortError') return;
            setErrMsg(e?.message ?? '목록을 불러오지 못했습니다.');
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    }

    useEffect(() => {
        // (1) 캐시 먼저 복원 → 있으면 “F5/명시적 새로고침” 전엔 서버 안 침
        const cached = sessionStorage.getItem(cacheKey);
        if (cached) {
            const parsed = JSON.parse(cached);
            setItems(parsed.items ?? []);
            setMeta(parsed.meta ?? null);
            setLoading(false);
            firstLoadRef.current = false;
            return;
        }

        // 최초 1회만 hard loading, 이후는 soft refresh (테이블 안 비움)
        load(firstLoadRef.current ? 'initial' : 'refresh');
        firstLoadRef.current = false;

        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [cacheKey]);

    // unmount 시 abort
    useEffect(() => {
        return () => abortRef.current?.abort();
    }, []);

    function openEdit(row: HospitalRow) {
        console.log('openEdit', row.id);
    }

    async function onDelete(id: number) {
        const ok = confirm('정말 삭제할까요? (soft delete)');
        if (!ok) return;

        try {
            const res = await fetch(`/admin/api/hospitals/${id}`, {
                method: 'DELETE',
                headers: { Accept: 'application/json' },
                credentials: 'include',
            });

            const json = (await res.json()) as ApiResponse<null>;
            if (!res.ok || !json.success) {
                throw new Error(json?.error?.message || `HTTP ${res.status}`);
            }

            setItems((prev) => prev.filter((x) => x.id !== id));
            setMeta((m) => (m ? { ...m, total: Math.max(0, m.total - 1) } : m));

            // 캐시도 같이 갱신 (현재 페이지 캐시)
            sessionStorage.setItem(
                cacheKey,
                JSON.stringify({
                    items: items.filter((x) => x.id !== id),
                    meta: meta ? { ...meta, total: Math.max(0, meta.total - 1) } : null,
                }),
            );
        } catch (e: any) {
            alert(e?.message ?? '삭제에 실패했습니다.');
        }
    }

    const columns = useMemo<DataTableColumn<HospitalRow>[]>(() => {
        return [
            {
                key: 'id',
                header: 'ID',
                render: (h) => <span className="font-medium">{h.id}</span>,
            },
            {
                key: 'name',
                header: '병원명',
                render: (h) => (
                    <div>
                        <div className="font-medium">{h.name}</div>
                        <div className="text-muted-foreground text-xs">
                            {h.address ?? '-'}
                        </div>
                    </div>
                ),
            },
            { key: 'tel', header: '연락처', render: (h) => h.tel ?? '-' },
            {
                key: 'view',
                header: '조회수',
                render: (h) => h.view_count.toLocaleString(),
            },
            {
                key: 'allow',
                header: '검수',
                render: (h) => (
                    <Badge
                        variant="light"
                        color={
                            h.allow_status === 'APPROVED'
                                ? 'success'
                                : h.allow_status === 'PENDING'
                                  ? 'warning'
                                  : 'error'
                        }
                        size="sm"
                    >
                        <span className="text-xs">
                            {allowLabel[h.allow_status]}
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
                        color={
                            h.status === 'ACTIVE'
                                ? 'success'
                                : h.status === 'SUSPENDED'
                                  ? 'warning'
                                  : 'error'
                        }
                        size="sm"
                    >
                        <span className="text-xs">{statusLabel[h.status]}</span>
                    </Badge>
                ),
            },
            {
                key: 'action',
                header: 'Action',
                render: (h) => (
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={(e) => {
                            e.stopPropagation();
                            onDelete(h.id);
                        }}
                    >
                        삭제
                    </Button>
                ),
            },
        ];
    }, []);

    return (
        <div className="px-3">
            <div className="overflow-hidden rounded-xl border border-gray-200 bg-white p-6 text-sm dark:border-white/[0.05] dark:bg-white/[0.03]">
                <DataTable
                    title=""
                    description={meta ? `총 ${meta.total}개` : undefined}
                    rightActions={
                        <Button className="bg-brand-500 text-white hover:bg-brand-600">
                            <Link href={hospitals.createHospitalForStaff().url}>
                                병원 등록
                            </Link>
                        </Button>
                    }
                    columns={columns}
                    rows={items}
                    getRowKey={(h) => h.id}
                    loading={loading}
                    error={errMsg}
                    meta={meta}
                    onGoPage={(p) => goPage(p)}
                    onRefresh={() => load('refresh')}
                    refreshing={refreshing}
                    onRowClick={(row) => openEdit(row)}
                />
            </div>
        </div>
    );
}

IndexHospitals.layout = (page: ReactNode) => (
    <AppLayout breadcrumbs={breadcrumbs}>{page}</AppLayout>
);

export default IndexHospitals;
