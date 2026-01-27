import { adminNavItems } from '@/components/admin-nav';
import { NavMain } from '@/components/nav-main';
import {
    Sidebar,
    SidebarContent,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes/admin';
import { Link } from '@inertiajs/react';
import AppLogo from './app-logo';

export function AppSidebar() {
    return (
        <Sidebar
            collapsible="icon"
            variant="inset"
            className="bg-white dark:bg-gray-900"
        >
            <SidebarHeader className="flex h-16 items-center border-gray-200 px-4 dark:border-gray-800 hidden lg:block">
                <SidebarMenu className="w-full">
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            size="lg"
                            asChild
                            className="h-12 w-full hover:bg-gray-50 dark:hover:bg-white/5"
                        >
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="px-2 py-2">
                <NavMain items={adminNavItems} />
            </SidebarContent>
        </Sidebar>
    );
}
