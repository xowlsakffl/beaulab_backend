import { AppContent } from '@/components/app-content';
import { AppHeader } from '@/components/app-header';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { type BreadcrumbItem } from '@/types';
import { type PropsWithChildren } from 'react';

interface AppLayoutProps extends PropsWithChildren {
    breadcrumbs?: BreadcrumbItem[];
}

export default function AppLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    return (
        <AppShell variant="sidebar">
            <AppSidebar />

            <div className="flex min-h-svh flex-1 flex-col">
                <AppHeader breadcrumbs={breadcrumbs} />

                <AppContent
                    variant="sidebar"
                    className="bg-gray-50 dark:bg-gray-950 min-h-0 flex-1 overflow-x-hidden overflow-y-auto"
                >
                    <div className="bg-gray-50 px-4 py-4 lg:p-8 dark:bg-gray-950">
                        {children}
                    </div>
                </AppContent>
            </div>
        </AppShell>
    );
}
