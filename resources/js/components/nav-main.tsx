import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useActiveUrl } from '@/hooks/use-active-url';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';

export function NavMain({ items = [] }: { items: NavItem[] }) {
    const { urlIsActive } = useActiveUrl();
    const { isMobile, openMobile, setOpenMobile } = useSidebar();

    return (
        <SidebarGroup className="px-3">
            <SidebarGroupLabel className="px-2 text-xs font-semibold tracking-wide text-gray-400">
                MENU
            </SidebarGroupLabel>

            <SidebarMenu className="mt-3 gap-1">
                {items.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton
                            asChild
                            size="lg"
                            isActive={urlIsActive(item.href)}
                            className="rounded-lg"
                        >
                            <Link
                                href={item.href}
                                prefetch
                                className="group px-3 text-gray-700 data-[active=true]:bg-brand-50 data-[active=true]:text-brand-600 dark:text-gray-300 dark:data-[active=true]:bg-white/5 dark:data-[active=true]:text-brand-400"
                                onClick={() => {
                                    if (isMobile && openMobile) {
                                        setOpenMobile(false);
                                    }
                                }}
                            >
                                {item.icon && (
                                    <item.icon className="h-5 w-5 text-gray-400 group-data-[active=true]:text-brand-600 dark:group-data-[active=true]:text-brand-400" />
                                )}
                                <span>{item.title}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
