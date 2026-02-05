import InputError from '@/components/input-error';
import AuthLayout from '@/layouts/admin/auth-layout';
import { Checkbox } from '@/components/ui/checkbox';
import { home } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Form, Head, Link } from '@inertiajs/react';
import { CheckCircle2, Eye, EyeOff, LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import { ChevronLeftIcon } from '../../../icons';
import { Alert, AlertTitle } from '@/components/ui/alert';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';

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
                                뷰랩 관리자에 로그인하세요!
                            </p>
                        </div>

                        {status && (
                            <Alert variant="success">
                                <CheckCircle2 />
                                <AlertTitle>{status}</AlertTitle>
                            </Alert>
                        )}

                        <Form
                            {...store.form()}
                            resetOnSuccess={['password']}
                            className="space-y-6"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div>
                                        <Label className="mb-1.5 block text-sm">
                                            <span className="text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                                아이디{' '}
                                            </span>
                                            <span className="text-error-500">
                                                *
                                            </span>
                                        </Label>

                                        <Input
                                            name="nickname"
                                            type="text"
                                            autoFocus
                                            tabIndex={1}
                                            autoComplete="nickname"
                                            placeholder="아이디를 입력하세요."
                                            error={!!errors.nickname}
                                        />

                                        <InputError
                                            message={errors.nickname}
                                            className="mt-2"
                                        />
                                    </div>

                                    <div>
                                        <Label className="mb-1.5 block text-sm">
                                            <span className="text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                                비밀번호{' '}
                                            </span>
                                            <span className="text-error-500">
                                                *
                                            </span>
                                        </Label>

                                        <div className="relative">
                                            <Input
                                                name="password"
                                                type={
                                                    showPassword
                                                        ? 'text'
                                                        : 'password'
                                                }
                                                autoComplete="current-password"
                                                placeholder="비밀번호를 입력하세요."
                                                error={!!errors.password}
                                                className="pr-11"
                                                inputMode="latin"
                                                lang="en"
                                                autoCapitalize="none"
                                                autoCorrect="off"
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
                                        <Label htmlFor="remember">
                                            <Checkbox
                                                id="remember"
                                                name="remember"
                                                tabIndex={3}
                                            />
                                            <span className="text-theme-sm font-normal text-gray-700 dark:text-gray-400">
                                                로그인 유지
                                            </span>
                                        </Label>

                                        {canResetPassword && (
                                            <Link href={request()} tabIndex={5}>
                                                <span className="text-theme-sm font-normal text-brand-500 hover:text-brand-600 dark:text-brand-400">
                                                    비밀번호를 잊으셨나요?
                                                </span>
                                            </Link>
                                        )}
                                    </div>

                                    <div>
                                        <Button
                                            type="submit"
                                            variant="brand"
                                            size="auth"
                                            className="w-full"
                                            tabIndex={4}
                                            disabled={processing}
                                            data-test="login-button"
                                        >
                                            {processing ? (
                                                <LoaderCircle className="h-4 w-4 animate-spin" />
                                            ) : null}
                                            로그인
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </div>
                </div>
            </div>
        </AuthLayout>
    );
}
