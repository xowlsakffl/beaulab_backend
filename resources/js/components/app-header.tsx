import AppHeaderBar from '@/components/app-header-bar';
import AppPageBar from '@/components/app-page-bar';
import { type BreadcrumbItem } from '@/types';
import { useState } from 'react';

interface AppHeaderProps {
    breadcrumbs?: BreadcrumbItem[];
}

export function AppHeader({ breadcrumbs = [] }: AppHeaderProps) {
    const [mobileActionsOpen, setMobileActionsOpen] = useState(false);

    return (
        <header
            data-admin-header
            className="sticky top-0 z-[70] w-full border-b border-gray-200 bg-white/80 backdrop-blur supports-[backdrop-filter]:bg-white/60 dark:border-gray-800 dark:bg-gray-900/80"
        >
            <AppHeaderBar
                mobileActionsOpen={mobileActionsOpen}
                onToggleMobileActions={() => setMobileActionsOpen((v) => !v)}
                onCloseMobileActions={() => setMobileActionsOpen(false)}
            />

            <AppPageBar
                breadcrumbs={breadcrumbs}
                mobileActionsOpen={mobileActionsOpen}
            />
        </header>
    );
}
