import { dashboard } from '@/routes/admin';
import hospitals from '@/routes/admin/hospitals';
import type { NavItem } from '@/types';
import { BarChart3, LayoutGrid, List, Plus } from 'lucide-react';

export const adminNavItems: NavItem[] = [
    { title: 'Dashboard', href: dashboard().url, icon: LayoutGrid },

    {
        title: '병원 관리',
        icon: BarChart3,
        children: [
            {
                title: '병원 목록',
                href: hospitals.indexHospitalPageForStaff().url,
                icon: List,
            },
            {
                title: '병원 등록',
                href: hospitals.createHospitalForStaff().url,
                icon: Plus,
            },
        ],
    },
    {
        title: '뷰티 관리',
        icon: BarChart3,
        children: [
            {
                title: '뷰티 목록',
                href: dashboard().url,
                icon: List,
            },
            {
                title: '뷰티 등록',
                href: dashboard().url,
                icon: Plus,
            },
        ],
    },
];
