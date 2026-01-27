import { calendar, dashboard, form, report } from '@/routes/admin';
import { type NavItem } from '@/types';
import { BarChart3, Calendar, LayoutGrid, SquarePen } from 'lucide-react';

export const adminNavItems: NavItem[] = [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
    { title: 'Report', href: report(), icon: BarChart3 },
    { title: 'Calendar', href: calendar(), icon: Calendar },
    { title: 'Form', href: form(), icon: SquarePen },
];
