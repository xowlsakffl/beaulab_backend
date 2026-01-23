import AppLayout from '@/layouts/admin/app-layout';
import type { ReactNode } from 'react';
import type { BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes/admin';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];
function ReportPage() {
    return (
        <>
            <div className="space-y-6 px-8">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white/90">
                        Reports
                    </h1>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        예시 페이지입니다. 데이터/차트/테이블을
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <div className="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                        <div className="text-sm text-gray-500 dark:text-gray-400">
                            이번달 매출
                        </div>
                        <div className="mt-2 text-2xl font-semibold">₩ 0</div>
                    </div>
                    <div className="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                        <div className="text-sm text-gray-500 dark:text-gray-400">
                            주문 수
                        </div>
                        <div className="mt-2 text-2xl font-semibold">0</div>
                    </div>
                    <div className="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                        <div className="text-sm text-gray-500 dark:text-gray-400">
                            신규 고객
                        </div>
                        <div className="mt-2 text-2xl font-semibold">0</div>
                    </div>
                </div>

                <div className="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <div className="text-sm text-gray-500 dark:text-gray-400">
                        여기에 차트 컴포넌트 넣기
                    </div>
                    <div className="mt-4 h-64 rounded-lg bg-gray-50 dark:bg-gray-950" />
                </div>
            </div>
        </>
    );
}

ReportPage.layout = (page: ReactNode) => (
    <AppLayout breadcrumbs={breadcrumbs}>{page}</AppLayout>
);
export default ReportPage;
