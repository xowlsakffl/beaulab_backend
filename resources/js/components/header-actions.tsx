import AppearanceToggle from '@/components/appearance-toggle';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';

export function HeaderActions() {
    const { auth } = usePage<SharedData>().props;
    const getInitials = useInitials();

    return (
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
                    className="mt-2 w-46 bg-white dark:bg-gray-900"
                    align="end"
                >
                    <UserMenuContent user={auth.user} />
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    );
}
