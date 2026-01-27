import { Breadcrumbs } from '@/components/breadcrumbs';
import { HeaderActions } from '@/components/header-actions';
import { type BreadcrumbItem } from '@/types';
import { useMemo } from 'react';

export default function AppPageBar({
    breadcrumbs = [],
    mobileActionsOpen = false,
}: {
    breadcrumbs?: BreadcrumbItem[];
    mobileActionsOpen?: boolean;
}) {
    const pageTitle = useMemo(
        () => breadcrumbs.at(-1)?.title ?? '',
        [breadcrumbs],
    );

    if (!breadcrumbs.length) return null;

    return (
        <div className="relative z-[70] w-full border-t border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div className="flex h-12 w-full items-center justify-between gap-4 px-4 md:px-4">
                <div className="min-w-0 text-xl font-bold text-gray-800 dark:text-white/90">
                    <span className="truncate">{pageTitle}</span>
                </div>

                <div className="hidden min-w-0 text-xs text-gray-500 lg:block dark:text-gray-400">
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>

                <div
                    className={[
                        'overflow-hidden duration-200 lg:hidden',
                        mobileActionsOpen
                            ? 'block'
                            : 'hidden',
                    ].join(' ')}
                >
                    <div>
                        <HeaderActions />
                    </div>
                </div>
            </div>
        </div>
    );
}
