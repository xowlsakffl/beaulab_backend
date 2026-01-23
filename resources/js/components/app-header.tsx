import AppPageBar from '@/components/app-page-bar';
import AppHeaderBar from '@/components/app-header-bar';
import { type BreadcrumbItem } from '@/types';

interface AppHeaderProps {
    breadcrumbs?: BreadcrumbItem[];
}

export function AppHeader({ breadcrumbs = [] }: AppHeaderProps) {
    return (
        <header className="sticky top-0 z-50 w-full border-b border-gray-200 bg-white/80 backdrop-blur supports-[backdrop-filter]:bg-white/60 dark:border-gray-800 dark:bg-gray-900/80">
            <AppHeaderBar />
            <AppPageBar breadcrumbs={breadcrumbs} />
        </header>
    );
}
