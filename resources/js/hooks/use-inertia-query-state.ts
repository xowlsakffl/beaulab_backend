import { router } from '@inertiajs/react';
import { useCallback, useRef, useState } from 'react';

export type QueryPrimitive = string | number | boolean | null | undefined;
export type QueryObject = Record<string, QueryPrimitive>;

export type InertiaQueryStateOptions<TFilters> = {
    url: string;
    only: string[];
    parse: (params: URLSearchParams) => TFilters;
    serialize: (filters: TFilters) => QueryObject;
    clean?: (query: QueryObject) => QueryObject;
};

export type UpdateOptions = {
    resetPage?: boolean;
    replace?: boolean;
    preserveScroll?: boolean;
    preserveState?: boolean;
};

/**
 * Inertia가 보통 허용하는 data 타입(FormDataConvertible 계열)로 맞추기 위해
 * - null/undefined 제거
 * - boolean/number는 string으로 변환 (query string 목적이라 더 안전)
 */
type InertiaData = Record<string, string>;

function defaultClean(query: QueryObject): QueryObject {
    const out: QueryObject = {};
    for (const [k, v] of Object.entries(query)) {
        if (v === null || v === undefined) continue;
        if (typeof v === 'string' && v.trim() === '') continue;
        out[k] = v;
    }
    return out;
}

function toInertiaData(q: QueryObject): InertiaData {
    const out: InertiaData = {};
    for (const [k, v] of Object.entries(q)) {
        if (v === null || v === undefined) continue;
        if (typeof v === 'string') {
            const t = v.trim();
            if (t === '') continue;
            out[k] = t;
            continue;
        }
        // number/boolean -> string
        out[k] = String(v);
    }
    return out;
}

function readCurrentParams(): URLSearchParams {
    if (typeof window === 'undefined') return new URLSearchParams();
    return new URLSearchParams(window.location.search);
}

export function useInertiaQueryState<TFilters extends Record<string, unknown>>(
    opts: InertiaQueryStateOptions<TFilters>,
) {
    const [pendingCount, setPendingCount] = useState(0);
    const refreshing = pendingCount > 0;

    const seqRef = useRef(0);

    const onStart = useCallback(() => setPendingCount((c) => c + 1), []);
    const onFinish = useCallback(
        () => setPendingCount((c) => Math.max(0, c - 1)),
        [],
    );

    const getCurrentFilters = useCallback((): TFilters => {
        const params = readCurrentParams();
        return opts.parse(params);
    }, [opts]);

    const toCleanQuery = useCallback(
        (filters: TFilters): QueryObject => {
            const query = opts.serialize(filters);
            const cleaner = opts.clean ?? defaultClean;
            return cleaner(query);
        },
        [opts],
    );

    const update = useCallback(
        (next: Partial<TFilters>, options?: UpdateOptions) => {
            if (refreshing) return;

            const current = getCurrentFilters();
            const merged = { ...current, ...next } as TFilters;

            if (options?.resetPage && 'page' in merged) {
                (merged as Record<string, unknown>).page = 1;
            }

            const data = toInertiaData(toCleanQuery(merged));
            const seq = ++seqRef.current;

            router.get(opts.url, data, {
                only: opts.only,
                replace: options?.replace ?? true,
                preserveScroll: options?.preserveScroll ?? true,
                preserveState: options?.preserveState ?? true,
                showProgress: false,
                onStart: () => {
                    onStart();
                },
                onFinish: () => {
                    void seq;
                    onFinish();
                },
            } as unknown as Parameters<typeof router.get>[2]);
        },
        [
            refreshing,
            getCurrentFilters,
            toCleanQuery,
            opts.url,
            opts.only,
            onStart,
            onFinish,
        ],
    );

    const reload = useCallback(() => {
        if (refreshing) return;

        router.reload({
            only: opts.only,
            showProgress: false,
            onStart,
            onFinish,
        } as unknown as Parameters<typeof router.reload>[0]);
    }, [refreshing, opts.only, onStart, onFinish]);

    type VisitOptions = Parameters<typeof router.visit>[1];

    const visit = useCallback(
        (url: string, options?: VisitOptions) => {
            if (refreshing) return;

            router.visit(url, {
                preserveScroll: true,
                showProgress: false,
                ...options,
                onStart: () => {
                    onStart();
                    options?.onStart?.();
                },
                onFinish: () => {
                    onFinish();
                    options?.onFinish?.();
                },
            } as VisitOptions);
        },
        [refreshing, onStart, onFinish],
    );

    const destroy = useCallback(
        (url: string, options?: Parameters<typeof router.delete>[1]) => {
            if (refreshing) return;

            router.delete(url, {
                preserveScroll: true,
                showProgress: false,
                ...options,
                onStart: () => {
                    onStart();
                    options?.onStart?.();
                },
                onFinish: () => {
                    onFinish();
                    options?.onFinish?.();
                },
            } as Parameters<typeof router.delete>[1]);
        },
        [refreshing, onStart, onFinish],
    );

    const post = useCallback(
        (
            url: string,
            data?: unknown,
            options?: Parameters<typeof router.post>[2],
        ) => {
            if (refreshing) return;

            router.post(url, data, {
                showProgress: false,
                ...options,
                onStart: () => {
                    onStart();
                    options?.onStart?.();
                },
                onFinish: () => {
                    onFinish();
                    options?.onFinish?.();
                },
            } as Parameters<typeof router.post>[2]);
        },
        [refreshing, onStart, onFinish],
    );

    const put = useCallback(
        (
            url: string,
            data?: unknown,
            options?: Parameters<typeof router.put>[2],
        ) => {
            if (refreshing) return;

            router.put(url, data, {
                showProgress: false,
                ...options,
                onStart: () => {
                    onStart();
                    options?.onStart?.();
                },
                onFinish: () => {
                    onFinish();
                    options?.onFinish?.();
                },
            } as Parameters<typeof router.put>[2]);
        },
        [refreshing, onStart, onFinish],
    );

    const patch = useCallback(
        (
            url: string,
            data?: unknown,
            options?: Parameters<typeof router.patch>[2],
        ) => {
            if (refreshing) return;

            router.patch(url, data, {
                showProgress: false,
                ...options,
                onStart: () => {
                    onStart();
                    options?.onStart?.();
                },
                onFinish: () => {
                    onFinish();
                    options?.onFinish?.();
                },
            } as Parameters<typeof router.patch>[2]);
        },
        [refreshing, onStart, onFinish],
    );

    const wrap = useCallback(
        <T extends (...args: unknown[]) => unknown>(fn: T) =>
            ((...args: Parameters<T>) => {
                if (refreshing) return;
                return fn(...args);
            }) as T,
        [refreshing],
    );

    return {
        refreshing,
        pendingCount,

        getCurrentFilters,
        update,
        reload,

        visit,
        delete: destroy,
        post,
        put,
        patch,

        wrap,
    };
}
