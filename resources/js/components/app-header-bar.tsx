import { SidebarTrigger } from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { EllipsisVertical , Search } from 'lucide-react';
import { useEffect, useRef } from 'react';

import AppLogo from '@/components/app-logo';
import { HeaderActions } from '@/components/header-actions';
import { dashboard } from '@/routes/admin';

export default function AppHeaderBar({
    mobileActionsOpen,
    onToggleMobileActions,
}: {
    mobileActionsOpen: boolean;
    onToggleMobileActions: () => void;
    onCloseMobileActions: () => void;
}) {
    const inputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        const handleKeyDown = (event: KeyboardEvent) => {
            if (
                (event.metaKey || event.ctrlKey) &&
                event.key.toLowerCase() === 'k'
            ) {
                event.preventDefault();
                inputRef.current?.focus();
            }
        };
        document.addEventListener('keydown', handleKeyDown);
        return () => document.removeEventListener('keydown', handleKeyDown);
    }, []);

    return (
        <div className="flex h-16 w-full items-center px-4 md:px-4">
            {/* 모바일 */}
            <div className="flex w-full items-center lg:hidden">
                <div className="flex w-1/3 items-center">
                    <SidebarTrigger className="h-10 w-10" />
                </div>

                <div className="flex w-1/3 justify-center">
                    <Link
                        href={dashboard()}
                        prefetch
                        className="flex items-center"
                    >
                        <AppLogo />
                    </Link>
                </div>

                <div className="flex w-1/3 justify-end">
                    <button
                        type="button"
                        aria-label="More"
                        onClick={onToggleMobileActions}
                        className={cn(
                            'flex items-center justify-center',
                            'border-gray-200 bg-white text-gray-700 hover:bg-gray-50',
                            'dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-white/5',
                            mobileActionsOpen && 'text-brand-500',
                        )}
                    >
                        <EllipsisVertical className="h-5 w-5" />
                    </button>
                </div>
            </div>

            {/* 데스크탑 */}
            <div className="hidden w-full items-center gap-3 lg:flex">
                <SidebarTrigger className="h-10 w-10" />

                {/* Search */}
                <div className="flex flex-1 items-center">
                    <div className="relative w-full max-w-xl">
                        <Search className="absolute top-1/2 left-4 h-5 w-5 -translate-y-1/2 text-gray-400" />
                        <input
                            ref={inputRef}
                            type="text"
                            placeholder="Search or type command..."
                            className={cn(
                                'h-11 w-full rounded-lg border bg-transparent py-2.5 pr-16 pl-12 text-sm text-gray-800 shadow-theme-xs outline-none placeholder:text-gray-400',
                                'border-gray-200 focus:border-brand-300 focus:ring-4 focus:ring-brand-500/10',
                                'dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800 dark:focus:ring-brand-500/20',
                            )}
                        />
                        <div className="pointer-events-none absolute top-1/2 right-2.5 hidden -translate-y-1/2 items-center gap-0.5 rounded-lg border border-gray-200 bg-gray-50 px-2 py-1 text-xs text-gray-500 sm:inline-flex dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400">
                            <span>⌘</span>
                            <span>K</span>
                        </div>
                    </div>
                </div>

                <HeaderActions />
            </div>
        </div>
    );
}
