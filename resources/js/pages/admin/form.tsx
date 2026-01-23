import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import AppLayout from '@/layouts/admin/app-layout';
import type { ReactNode } from 'react';
import { useState } from 'react';
import type { BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes/admin';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];
function FormPage() {
    const [checked, setChecked] = useState(false);

    return (
        <>
            <div className="space-y-6 px-8">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white/90">
                        Forms
                    </h1>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        로그인 페이지 스타일(brand focus ring)로 폼을 구성하는
                        예시입니다.
                    </p>
                </div>

                <div className="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <div className="space-y-6">
                        <div>
                            <label className="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                제목 <span className="text-error-500">*</span>
                            </label>

                            <input
                                name="title"
                                placeholder="제목을 입력하세요."
                                className={[
                                    'h-11 w-full rounded-lg border bg-white px-4 text-sm text-gray-900 transition outline-none',
                                    'border-gray-200 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10',
                                    'dark:border-gray-800 dark:bg-gray-950 dark:text-white/90 dark:focus:ring-brand-500/20',
                                ].join(' ')}
                            />

                            <InputError message={undefined} className="mt-2" />
                        </div>

                        <div className="flex items-center gap-3">
                            <Checkbox
                                id="demo-check"
                                checked={checked}
                                onCheckedChange={(v) => setChecked(Boolean(v))}
                            />
                            <label
                                htmlFor="demo-check"
                                className="text-sm text-gray-700 dark:text-gray-400"
                            >
                                동의합니다
                            </label>
                        </div>

                        <button
                            type="button"
                            className="inline-flex w-full items-center justify-center rounded-lg bg-brand-500 px-5 py-3 text-sm font-medium text-white transition hover:bg-brand-600"
                        >
                            저장
                        </button>
                    </div>
                </div>
            </div>
        </>
    );
}

FormPage.layout = (page: ReactNode) => (
    <AppLayout breadcrumbs={breadcrumbs}>{page}</AppLayout>
);
export default FormPage;
