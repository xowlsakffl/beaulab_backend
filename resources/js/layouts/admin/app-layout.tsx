import { AppContent } from '@/components/app-content';
import { AppHeader } from '@/components/app-header';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { type BreadcrumbItem } from '@/types';
import { type PropsWithChildren } from 'react';
import FlashToast from '@/components/ui/flash-alert';

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

            <div className="flex min-h-svh min-w-0 flex-1 flex-col">
                <AppHeader breadcrumbs={breadcrumbs} />

                <AppContent
                    variant="sidebar"
                    className="min-h-0 flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 dark:bg-gray-950"
                >
                    <div className="bg-gray-50 p-3 lg:p-6 dark:bg-gray-950">
                        {children}
                    </div>
                </AppContent>
            </div>
        </AppShell>
    );
}
