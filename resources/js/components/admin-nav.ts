import { dashboard } from '@/routes/admin';
import { type NavItem } from '@/types';
import { BarChart3, LayoutGrid } from 'lucide-react';
import hospitals from '@/routes/admin/hospitals';

export const adminNavItems: NavItem[] = [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
    {
        title: '병원 관리',
        href: hospitals.indexPageForStaff(),
        icon: BarChart3,
    },
];
