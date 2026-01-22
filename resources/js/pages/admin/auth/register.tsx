import InputError from '@/components/input-error';
import AuthLayout from '@/layouts/admin/auth-layout';
import { home } from '@/routes';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { ChevronLeftIcon } from '@/icons';

export default function Register() {
    return (
        <AuthLayout>
            <Head title="입점 신청" />

            <Link
                href={home()}
                className="mb-6 inline-flex items-center gap-2 text-sm text-gray-500 transition-colors hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
            >
                <ChevronLeftIcon className="size-5" />
                홈으로 돌아가기
            </Link>

            <div className="mb-5 sm:mb-8">
                <h1 className="mb-2 text-title-sm font-semibold text-gray-800 sm:text-title-md dark:text-white/90">
                    입점 신청
                </h1>
                <p className="text-sm text-gray-500 dark:text-gray-400">
                    신청 후 검토/승인되면 관리자 권한을 부여해드려요.
                </p>
            </div>

            <Form
                // TODO: 입점신청 전용 store로 교체 필요
                // {...store.form()}
                method="post"
                action="#"
                className="space-y-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div>
                            <label className="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                업체명 <span className="text-error-500">*</span>
                            </label>
                            <input
                                name="company_name"
                                type="text"
                                autoFocus
                                placeholder="업체명을 입력하세요."
                                className={[
                                    'h-11 w-full rounded-lg border bg-white px-4 text-sm text-gray-900 transition outline-none',
                                    'border-gray-200 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10',
                                    'dark:border-gray-800 dark:bg-gray-950 dark:text-white/90 dark:focus:ring-brand-500/20',
                                    errors.company_name
                                        ? 'border-error-500 focus:border-error-500 focus:ring-error-500/10 dark:focus:ring-error-500/20'
                                        : '',
                                ].join(' ')}
                            />
                            <InputError
                                // @ts-expect-error - depends on backend validation bag
                                message={errors.company_name}
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <label className="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                이메일 <span className="text-error-500">*</span>
                            </label>
                            <input
                                name="email"
                                type="email"
                                placeholder="email@example.com"
                                className={[
                                    'h-11 w-full rounded-lg border bg-white px-4 text-sm text-gray-900 transition outline-none',
                                    'border-gray-200 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10',
                                    'dark:border-gray-800 dark:bg-gray-950 dark:text-white/90 dark:focus:ring-brand-500/20',
                                    errors.email
                                        ? 'border-error-500 focus:border-error-500 focus:ring-error-500/10 dark:focus:ring-error-500/20'
                                        : '',
                                ].join(' ')}
                            />
                            <InputError
                                message={errors.email}
                                className="mt-2"
                            />
                        </div>

                        <button
                            type="submit"
                            className="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand-500 px-5 py-3 text-sm font-medium text-white transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-70"
                            disabled={processing}
                            data-test="vendor-apply-button"
                        >
                            {processing && (
                                <LoaderCircle className="h-4 w-4 animate-spin" />
                            )}
                            신청하기
                        </button>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
