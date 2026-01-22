import InputError from '@/components/input-error';
import AuthLayout from '@/layouts/admin/auth-layout';
import { Checkbox } from '@/components/ui/checkbox';
import { home } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Form, Head, Link } from '@inertiajs/react';
import { Eye, EyeOff, LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import { ChevronLeftIcon } from '../../../icons';

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}

export default function Login({
    status,
    canResetPassword,
    // canRegister,
}: LoginProps) {
    const [showPassword, setShowPassword] = useState(false);

    return (
        <AuthLayout>
            <Head title="로그인" />

            <Link
                href={home()}
                className="mb-6 inline-flex items-center gap-2 text-sm text-gray-500 transition-colors hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
            >
                <ChevronLeftIcon className="size-5" />
                홈으로 돌아가기
            </Link>

            <div className="flex flex-1 flex-col">
                <div className="mx-auto flex w-full max-w-md flex-1 flex-col justify-center">
                    <div>
                        <div className="mb-5 sm:mb-8">
                            <h1 className="mb-2 text-title-sm font-semibold text-gray-800 sm:text-title-md dark:text-white/90">
                                로그인
                            </h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                이메일과 비밀번호를 입력해 뷰랩 관리자에
                                로그인하세요!
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
                                            아이디{' '}
                                            <span className="text-error-500">
                                                *
                                            </span>
                                        </label>

                                        <input
                                            name="nickname"
                                            type="text"
                                            autoFocus
                                            tabIndex={1}
                                            autoComplete="nickname"
                                            placeholder="아이디를 입력하세요."
                                            className={[
                                                'h-11 w-full rounded-lg border bg-white px-4 text-sm text-gray-900 transition outline-none',
                                                'border-gray-200 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10',
                                                'dark:border-gray-800 dark:bg-gray-950 dark:text-white/90 dark:focus:ring-brand-500/20',
                                                errors.nickname
                                                    ? 'border-error-500 focus:border-error-500 focus:ring-error-500/10 dark:focus:ring-error-500/20'
                                                    : '',
                                            ].join(' ')}
                                        />

                                        <InputError
                                            message={errors.nickname}
                                            className="mt-2"
                                        />
                                    </div>

                                    <div>
                                        <label className="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            비밀번호{' '}
                                            <span className="text-error-500">
                                                *
                                            </span>
                                        </label>

                                        <div className="relative">
                                            <input
                                                name="password"
                                                type={
                                                    showPassword
                                                        ? 'text'
                                                        : 'password'
                                                }
                                                tabIndex={2}
                                                autoComplete="current-password"
                                                placeholder="비밀번호를 입력하세요."
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
                                                onClick={() =>
                                                    setShowPassword((v) => !v)
                                                }
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

                                    <div className="flex items-center justify-between">
                                        <label className="flex items-center gap-3">
                                            <Checkbox
                                                id="remember"
                                                name="remember"
                                                tabIndex={3}
                                            />
                                            <span className="block text-theme-sm font-normal text-gray-700 dark:text-gray-400">
                                                로그인 유지
                                            </span>
                                        </label>

                                        {canResetPassword && (
                                            <Link
                                                href={request()}
                                                className="text-sm text-brand-500 hover:text-brand-600 dark:text-brand-400"
                                                tabIndex={5}
                                            >
                                                비밀번호를 잊으셨나요?
                                            </Link>
                                        )}
                                    </div>

                                    <div>
                                        <button
                                            type="submit"
                                            className="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand-500 px-5 py-3 text-sm font-medium text-white transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-70"
                                            tabIndex={4}
                                            disabled={processing}
                                            data-test="login-button"
                                        >
                                            {processing && (
                                                <LoaderCircle className="h-4 w-4 animate-spin" />
                                            )}
                                            로그인
                                        </button>
                                    </div>
                                </>
                            )}
                        </Form>

                        {status && (
                            <div className="mt-5 text-center text-sm font-medium text-green-600">
                                {status}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthLayout>
    );
}
