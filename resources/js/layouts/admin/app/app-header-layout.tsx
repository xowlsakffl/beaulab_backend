import { AppContent } from '@/components/app-content';
import { AppHeaderBar } from '@/components/app-header-bar';
import { AppShell } from '@/components/app-shell';
import { type BreadcrumbItem } from '@/types';
import type { PropsWithChildren } from 'react';

export default function AppHeaderLayout({
    children,
    breadcrumbs,
}: PropsWithChildren<{ breadcrumbs?: BreadcrumbItem[] }>) {
    return (
        <AppShell>
            <AppHeaderBar breadcrumbs={breadcrumbs} />
            <AppContent>
                <div className="relative flex-1 bg-gray-50 p-4 sm:p-6 lg:p-8 dark:bg-gray-950">
                    {children}
                </div>
            </AppContent>
        </AppShell>
    );
}
