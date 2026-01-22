import InputError from '@/components/input-error';
import AuthLayout from '@/layouts/admin/auth-layout';
import { store } from '@/routes/password/confirm';
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

export default function ConfirmPassword() {
    return (
        <AuthLayout>
            <Head title="Confirm password" />

            <div className="mb-5 sm:mb-8">
                <h1 className="mb-2 text-title-sm font-semibold text-gray-800 sm:text-title-md dark:text-white/90">
                    비밀번호 확인
                </h1>
                <p className="text-sm text-gray-500 dark:text-gray-400">
                    보안을 위해 비밀번호를 다시 입력해주세요.
                </p>
            </div>

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="space-y-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div>
                            <label className="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                비밀번호{' '}
                                <span className="text-error-500">*</span>
                            </label>

                            <input
                                name="password"
                                type="password"
                                placeholder="비밀번호를 입력하세요."
                                autoComplete="current-password"
                                autoFocus
                                className={[
                                    'h-11 w-full rounded-lg border bg-white px-4 text-sm text-gray-900 transition outline-none',
                                    'border-gray-200 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10',
                                    'dark:border-gray-800 dark:bg-gray-950 dark:text-white/90 dark:focus:ring-brand-500/20',
                                    errors.password
                                        ? 'border-error-500 focus:border-error-500 focus:ring-error-500/10 dark:focus:ring-error-500/20'
                                        : '',
                                ].join(' ')}
                            />

                            <InputError
                                message={errors.password}
                                className="mt-2"
                            />
                        </div>

                        <button
                            type="submit"
                            className="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand-500 px-5 py-3 text-sm font-medium text-white transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-70"
                            disabled={processing}
                            data-test="confirm-password-button"
                        >
                            {processing && (
                                <LoaderCircle className="h-4 w-4 animate-spin" />
                            )}
                            확인
                        </button>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
