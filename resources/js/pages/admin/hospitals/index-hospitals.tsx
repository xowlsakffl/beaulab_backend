import AppLayout from '@/layouts/admin/app-layout';
import { dashboard } from '@/routes/admin';
import type { BreadcrumbItem } from '@/types';
import type { ReactNode } from 'react';
import hospitals from '@/routes/admin/hospitals';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: '홈',
        href: dashboard().url,
    },
    {
        title: '병원 목록',
        href: hospitals.indexPageForStaff().url,
    },
];
function IndexHospitals() {
    return (
        <>
            <div className="space-y-6 px-8">
                <div className="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <div className="text-sm text-gray-500 dark:text-gray-400">
                        캘린더 영역(placeholder)
                    </div>
                    <div className="mt-4 h-[520px] rounded-lg bg-gray-50 dark:bg-gray-950" />
                </div>
            </div>
        </>
    );
}

IndexHospitals.layout = (page: ReactNode) => (
    <AppLayout breadcrumbs={breadcrumbs}>{page}</AppLayout>
);

export default IndexHospitals;
