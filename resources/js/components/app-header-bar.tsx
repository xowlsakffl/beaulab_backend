import AppearanceToggle from '@/components/appearance-toggle';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Bell, Menu, Search } from 'lucide-react';
import { useEffect, useRef } from 'react';
import AppLogoIcon from './app-logo-icon';

export default function AppHeaderBar() {
    const { auth } = usePage<SharedData>().props;
    const getInitials = useInitials();

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
        <div className="flex h-16 w-full items-center gap-3 px-4 md:px-6">
            <SidebarTrigger className="h-10 w-10" />

            {/* Mobile menu (지금은 유지, 나중에 SidebarTrigger만으로 통합 가능) */}
            <div className="lg:hidden">
                <Sheet>
                    <SheetTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="h-10 w-10"
                        >
                            <Menu className="h-5 w-5" />
                        </Button>
                    </SheetTrigger>
                    <SheetContent
                        side="left"
                        className="flex h-full w-72 flex-col justify-between bg-white dark:bg-gray-900"
                    >
                        <SheetTitle className="sr-only">Navigation</SheetTitle>
                        <SheetHeader className="flex justify-start text-left">
                            <Link
                                href={dashboard()}
                                className="inline-flex items-center gap-2"
                            >
                                <span className="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-brand-500 text-white">
                                    <AppLogoIcon className="h-5 w-5 text-white" />
                                </span>
                                <span className="font-semibold text-gray-900 dark:text-white/90">
                                    Beaulab
                                </span>
                            </Link>
                        </SheetHeader>

                        <div className="p-4 text-sm text-gray-500 dark:text-gray-400">
                            모바일 메뉴는 사이드바 컴포넌트와 통합.
                        </div>
                    </SheetContent>
                </Sheet>
            </div>

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

            {/* Right actions */}
            <div className="flex items-center gap-4">
                <AppearanceToggle />

                <Button
                    variant="ghost"
                    size="icon"
                    className="h-10 w-10 rounded-full border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-white/5"
                >
                    <Bell className="h-5 w-5" />
                    <span className="sr-only">Notifications</span>
                </Button>

                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="ghost"
                            className="h-10 w-10 rounded-full p-1"
                        >
                            <Avatar className="size-10 overflow-hidden rounded-full">
                                <AvatarImage
                                    src={auth.user.avatar}
                                    alt={auth.user.name}
                                />
                                <AvatarFallback className="rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                    {getInitials(auth.user.name)}
                                </AvatarFallback>
                            </Avatar>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="dark:bg-gray-900 w-46 bg-white mt-2"
                        align="end"
                    >
                        <UserMenuContent user={auth.user} />
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>
    );
}
