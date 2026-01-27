import { AppContent } from '@/components/app-content';
import { AppHeader } from '@/components/app-header';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { type BreadcrumbItem } from '@/types';
import { type PropsWithChildren } from 'react';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: PropsWithChildren<{ breadcrumbs?: BreadcrumbItem[] }>) {
    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <div className="flex min-h-svh flex-1 flex-col">
                <AppHeader breadcrumbs={breadcrumbs} />

                <AppContent variant="sidebar" className="overflow-x-hidden">
                    <div className="flex-1 bg-gray-50 py-4 lg:p-8 dark:bg-gray-950">
                        {children}
                    </div>
                </AppContent>
            </div>
        </AppShell>
    );
}
