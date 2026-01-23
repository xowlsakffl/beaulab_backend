import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { calendar, dashboard, form, report } from '@/routes/admin';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import {
    BarChart3,
    BookOpen,
    Calendar,
    Folder,
    LayoutGrid,
    SquarePen,
} from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
    { title: 'Report', href: report(), icon: BarChart3 },
    { title: 'Calendar', href: calendar(), icon: Calendar },
    { title: 'Form', href: form(), icon: SquarePen },
];

const footerNavItems: NavItem[] = [
    { title: '테스트', href: '#', icon: Folder },
    { title: '테스트', href: '#', icon: BookOpen },
];

export function AppSidebar() {
    return (
        <Sidebar
            collapsible="icon"
            variant="inset"
            className="bg-white dark:bg-gray-900"
        >
            <SidebarHeader className="flex h-16 items-center border-gray-200 px-4 dark:border-gray-800">
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
                <NavMain items={mainNavItems} />
            </SidebarContent>

            {/*<SidebarFooter className="border-t border-gray-200 px-2 py-3 dark:border-gray-800">
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>*/}
        </Sidebar>
    );
}
