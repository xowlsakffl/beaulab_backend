import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useActiveUrl } from '@/hooks/use-active-url';
import { cn } from '@/lib/utils';
import type { NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import * as React from 'react';

export function NavMain({ items = [] }: { items: NavItem[] }) {
    const { urlIsActive } = useActiveUrl();
    const { isMobile, openMobile, setOpenMobile } = useSidebar();
    const [openMap, setOpenMap] = React.useState<Record<string, boolean>>({});

    const closeMobileIfNeeded = React.useCallback(() => {
        if (isMobile && openMobile) setOpenMobile(false);
    }, [isMobile, openMobile, setOpenMobile]);

    const isChildActive = React.useCallback(
        (children?: NavItem[]) =>
            !!children?.some((c) => (c.href ? urlIsActive(c.href) : false)),
        [urlIsActive],
    );

    return (
        <SidebarGroup className="px-3">
            <SidebarGroupLabel className="px-2 text-xs tracking-wide text-gray-400">
                MENU
            </SidebarGroupLabel>

            <SidebarMenu className="mt-3 gap-1">
                {items.map((item) => {
                    const key = item.title;
                    const hasChildren = !!(item as any).children?.length;

                    const selfActive = item.href ? urlIsActive(item.href) : false;
                    const childActive = hasChildren ? isChildActive((item as any).children) : false;
                    const active = selfActive || childActive;

                    // open은 active랑 분리
                    const isOpen = hasChildren
                        ? (openMap[key] ?? active)
                        : false;

                    return (
                        <SidebarMenuItem key={key}>
                            {/* 부모(그룹) */}
                            {hasChildren ? (
                                <SidebarMenuButton
                                    type="button"
                                    size="lg"
                                    isActive={active}
                                    className={cn(
                                        'group rounded-lg px-3 text-gray-700 dark:text-gray-300',
                                        'data-[active=true]:bg-brand-50 data-[active=true]:text-brand-600',
                                        'dark:data-[active=true]:bg-white/5 dark:data-[active=true]:text-brand-400',
                                    )}
                                    onClick={() => {
                                        setOpenMap((prev) => {
                                            const nextOpen = !(
                                                prev[key] ?? false
                                            );
                                            return nextOpen
                                                ? { [key]: true }
                                                : {};
                                        });
                                    }}
                                >
                                    {item.icon ? (
                                        <item.icon
                                            className={cn(
                                                'h-5 w-5 text-gray-400',
                                                'group-data-[active=true]:text-brand-600 dark:group-data-[active=true]:text-brand-400',
                                            )}
                                        />
                                    ) : null}

                                    <span className="flex-1">{item.title}</span>

                                    <ChevronDown
                                        className={cn(
                                            'h-6 w-6 text-gray-400 transition-transform',
                                            isOpen ? 'rotate-180' : 'rotate-0',
                                            'group-data-[active=true]:text-brand-600 dark:group-data-[active=true]:text-brand-400',
                                        )}
                                    />
                                </SidebarMenuButton>
                            ) : (
                                /* leaf(단일 링크) */
                                <SidebarMenuButton
                                    asChild
                                    size="lg"
                                    isActive={active}
                                    className={cn(
                                        'group rounded-lg px-3 text-gray-700 dark:text-gray-300',
                                        'data-[active=true]:bg-brand-50 data-[active=true]:text-brand-600',
                                        'dark:data-[active=true]:bg-white/5 dark:data-[active=true]:text-brand-400',
                                    )}
                                >
                                    <Link
                                        href={item.href!}
                                        prefetch
                                        onClick={() => {
                                            // 어떤 메뉴 클릭이든 모두 닫기
                                            setOpenMap({});
                                            closeMobileIfNeeded();
                                        }}
                                    >
                                        {item.icon ? (
                                            <item.icon
                                                className={cn(
                                                    'h-5 w-5 text-gray-400',
                                                    'group-data-[active=true]:text-brand-600 dark:group-data-[active=true]:text-brand-400',
                                                )}
                                            />
                                        ) : null}
                                        <span>{item.title}</span>
                                    </Link>
                                </SidebarMenuButton>
                            )}

                            {/* 자식(2단) */}
                            {hasChildren ? (
                                <div
                                    className={cn(
                                        'grid transition-[grid-template-rows] duration-200 ease-out',
                                        isOpen
                                            ? 'grid-rows-[1fr]'
                                            : 'grid-rows-[0fr]',
                                    )}
                                >
                                    <div className="min-h-0 overflow-hidden">
                                        <SidebarMenuSub className="mx-0 mt-1 border-0 px-0">
                                            {(item as any).children!.map(
                                                (child: NavItem) => {
                                                    if (!child.href)
                                                        return null;

                                                    const childKey = `${key}::${child.title}`;
                                                    const childIsActive =
                                                        urlIsActive(child.href);

                                                    return (
                                                        <SidebarMenuSubItem
                                                            key={childKey}
                                                        >
                                                            <SidebarMenuSubButton
                                                                asChild
                                                                isActive={
                                                                    childIsActive
                                                                }
                                                                className="ml-9"
                                                            >
                                                                <Link
                                                                    href={
                                                                        child.href
                                                                    }
                                                                    prefetch
                                                                    onClick={() => {
                                                                        // 다른 부모 닫고, 이 부모만 유지
                                                                        setOpenMap(
                                                                            {
                                                                                [key]: true,
                                                                            },
                                                                        );
                                                                        closeMobileIfNeeded();
                                                                    }}
                                                                    className="flex items-center gap-2"
                                                                >
                                                                    <span>
                                                                        {
                                                                            child.title
                                                                        }
                                                                    </span>
                                                                </Link>
                                                            </SidebarMenuSubButton>
                                                        </SidebarMenuSubItem>
                                                    );
                                                },
                                            )}
                                        </SidebarMenuSub>
                                    </div>
                                </div>
                            ) : null}
                        </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
