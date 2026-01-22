import { update } from '@/routes/password';
import { Form, Head } from '@inertiajs/react';

import InputError from '@/components/input-error';
import AuthLayout from '@/layouts/admin/auth-layout';
import { Eye, EyeOff, LoaderCircle } from 'lucide-react';
import { useState } from 'react';

interface ResetPasswordProps {
    token: string;
    email: string;
}

export default function ResetPassword({ token, email }: ResetPasswordProps) {
    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirm, setShowPasswordConfirm] = useState(false);

    return (
        <AuthLayout>
            <Head title="Reset password" />

            <div className="mb-5 sm:mb-8">
                <h1 className="mb-2 text-title-sm font-semibold text-gray-800 sm:text-title-md dark:text-white/90">
                    비밀번호 재설정
                </h1>
                <p className="text-sm text-gray-500 dark:text-gray-400">
                    새 비밀번호를 입력해주세요.
                </p>
            </div>

            <Form
                {...update.form()}
                transform={(data) => ({ ...data, token, email })}
                resetOnSuccess={['password', 'password_confirmation']}
                className="space-y-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div>
                            <label className="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                이메일
                            </label>

                            <input
                                name="email"
                                type="email"
                                value={email}
                                readOnly
                                className={[
                                    'h-11 w-full rounded-lg border bg-gray-50 px-4 text-sm text-gray-900 outline-none',
                                    'border-gray-200',
                                    'dark:border-gray-800 dark:bg-gray-900 dark:text-white/90',
                                ].join(' ')}
                            />

                            <InputError
                                message={errors.email}
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <label className="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                새 비밀번호{' '}
                                <span className="text-error-500">*</span>
                            </label>

                            <div className="relative">
                                <input
                                    name="password"
                                    type={showPassword ? 'text' : 'password'}
                                    autoComplete="new-password"
                                    autoFocus
                                    placeholder="새 비밀번호를 입력하세요."
                                    className={[
                                        'h-11 w-full rounded-lg border bg-white px-4 pr-11 text-sm text-gray-900 transition outline-none',
                                        'border-gray-200 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10',
                                        'dark:border-gray-800 dark:bg-gray-950 dark:text-white/90 dark:focus:ring-brand-500/20',
                                        errors.password
                                            ? 'border-error-500 focus:border-error-500 focus:ring-error-500/10 dark:focus:ring-error-500/20'
                                            : '',
                                    ].join(' ')}
                                />

                                <button
                                    type="button"
                                    onClick={() => setShowPassword((v) => !v)}
                                    className="absolute top-1/2 right-3 z-30 -translate-y-1/2 rounded-md p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                    aria-label={
                                        showPassword
                                            ? 'Hide password'
                                            : 'Show password'
                                    }
                                >
                                    {showPassword ? (
                                        <Eye className="h-5 w-5" />
                                    ) : (
                                        <EyeOff className="h-5 w-5" />
                                    )}
                                </button>
                            </div>

                            <InputError
                                message={errors.password}
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <label className="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                비밀번호 확인{' '}
                                <span className="text-error-500">*</span>
                            </label>

                            <div className="relative">
                                <input
                                    name="password_confirmation"
                                    type={
                                        showPasswordConfirm
                                            ? 'text'
                                            : 'password'
                                    }
                                    autoComplete="new-password"
                                    placeholder="비밀번호를 다시 입력하세요."
                                    className={[
                                        'h-11 w-full rounded-lg border bg-white px-4 pr-11 text-sm text-gray-900 transition outline-none',
                                        'border-gray-200 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10',
                                        'dark:border-gray-800 dark:bg-gray-950 dark:text-white/90 dark:focus:ring-brand-500/20',
                                        errors.password_confirmation
                                            ? 'border-error-500 focus:border-error-500 focus:ring-error-500/10 dark:focus:ring-error-500/20'
                                            : '',
                                    ].join(' ')}
                                />

                                <button
                                    type="button"
                                    onClick={() =>
                                        setShowPasswordConfirm((v) => !v)
                                    }
                                    className="absolute top-1/2 right-3 z-30 -translate-y-1/2 rounded-md p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                    aria-label={
                                        showPasswordConfirm
                                            ? 'Hide password confirmation'
                                            : 'Show password confirmation'
                                    }
                                >
                                    {showPasswordConfirm ? (
                                        <Eye className="h-5 w-5" />
                                    ) : (
                                        <EyeOff className="h-5 w-5" />
                                    )}
                                </button>
                            </div>

                            <InputError
                                message={errors.password_confirmation}
                                className="mt-2"
                            />
                        </div>

                        <button
                            type="submit"
                            className="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand-500 px-5 py-3 text-sm font-medium text-white transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-70"
                            disabled={processing}
                            data-test="reset-password-button"
                        >
                            {processing && (
                                <LoaderCircle className="h-4 w-4 animate-spin" />
                            )}
                            비밀번호 재설정
                        </button>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
