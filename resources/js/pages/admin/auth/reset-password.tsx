import { update } from '@/routes/password';
import { Form, Head } from '@inertiajs/react';

import InputError from '@/components/input-error';
import AuthLayout from '@/layouts/admin/auth-layout';
import { Eye, EyeOff, LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

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
                            <Label className="mb-1.5 block text-sm">
                                <span className="text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                    이메일
                                </span>
                            </Label>

                            <Input
                                name="email"
                                type="email"
                                value={email}
                                readOnly
                                className="bg-gray-200 text-gray-400 dark:bg-gray-800 dark:text-gray-400"
                            />

                            <InputError
                                message={errors.email}
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <Label className="mb-1.5 block text-sm">
                                <span className="text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                    새 비밀번호{' '}
                                </span>
                                <span className="text-error-500">*</span>
                            </Label>

                            <div className="relative">
                                <Input
                                    name="password"
                                    type={showPassword ? 'text' : 'password'}
                                    autoComplete="new-password"
                                    autoFocus
                                    placeholder="새 비밀번호를 입력하세요."
                                    error={!!errors.password}
                                    className="pr-11"
                                    inputMode="latin"
                                    lang="en"
                                    autoCapitalize="none"
                                    autoCorrect="off"
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
                            <Label className="mb-1.5 block text-sm">
                                <span className="text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                    비밀번호 확인{' '}
                                </span>
                                <span className="text-error-500">*</span>
                            </Label>

                            <div className="relative">
                                <Input
                                    name="password_confirmation"
                                    type={
                                        showPasswordConfirm
                                            ? 'text'
                                            : 'password'
                                    }
                                    autoComplete="new-password"
                                    autoFocus
                                    placeholder="비밀번호를 다시 입력하세요."
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

                        <Button
                            type="submit"
                            variant="brand"
                            size="auth"
                            className="w-full"
                            tabIndex={4}
                            disabled={processing}
                            data-test="reset-password-button"
                        >
                            {processing && (
                                <LoaderCircle className="h-4 w-4 animate-spin" />
                            )}
                            비밀번호 재설정
                        </Button>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
