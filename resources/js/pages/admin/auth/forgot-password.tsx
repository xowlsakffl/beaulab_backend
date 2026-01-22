// Components
import InputError from '@/components/input-error';
import { ChevronLeftIcon } from '@/icons';
import AuthLayout from '@/layouts/admin/auth-layout';
import { login } from '@/routes';
import { email } from '@/routes/password';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

export default function ForgotPassword({ status }: { status?: string }) {
    return (
        <AuthLayout>
            <Head title="Forgot password" />

            <Link
                href={login()}
                className="mb-6 inline-flex items-center gap-2 text-sm text-gray-500 transition-colors hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
            >
                <ChevronLeftIcon className="size-5" />
                로그인
            </Link>

            <div className="mb-5 sm:mb-8">
                <h1 className="mb-2 text-title-sm font-semibold text-gray-800 sm:text-title-md dark:text-white/90">
                    비밀번호 재설정
                </h1>
                <p className="text-sm text-gray-500 dark:text-gray-400">
                    비밀번호 재설정 링크를 받으려면 이메일 주소를 입력하세요.
                </p>
            </div>

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <Form {...email.form()} className="space-y-6">
                {({ processing, errors }) => (
                    <>
                        <div>
                            <label className="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                이메일 <span className="text-error-500">*</span>
                            </label>

                            <input
                                name="email"
                                type="email"
                                autoComplete="off"
                                autoFocus
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
                            data-test="email-password-reset-link-button"
                        >
                            {processing && (
                                <LoaderCircle className="h-4 w-4 animate-spin" />
                            )}
                            재설정 링크 보내기
                        </button>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
