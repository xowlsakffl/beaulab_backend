import AppLayout from '@/layouts/admin/app-layout';
import { dashboard } from '@/routes/admin';
import type { BreadcrumbItem } from '@/types';
import type { ReactNode } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: '병원 목록',
        href: dashboard().url,
    },
];
function IndexHospitals() {
    return (
        <>
            <div className="space-y-6 px-8">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white/90">
                        Calendar
                    </h1>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        FullCalendar 같은 컴포넌트를 여기에 붙이면 됩니다.
                    </p>
                </div>

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
