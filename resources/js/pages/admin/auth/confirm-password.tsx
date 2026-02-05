import InputError from '@/components/input-error';
import AuthLayout from '@/layouts/admin/auth-layout';
import { store } from '@/routes/password/confirm';
import { Form, Head } from '@inertiajs/react';
import { Eye, EyeOff, LoaderCircle } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { useState } from 'react';
import { Button } from '@/components/ui/button';

export default function ConfirmPassword() {
    const [showPassword, setShowPassword] = useState(false);

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
                            <Label className="mb-1.5 block text-sm">
                                <span className="text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                    비밀번호{' '}
                                </span>
                                <span className="text-error-500">*</span>
                            </Label>

                            <div className="relative">
                                <Input
                                    name="password"
                                    type={showPassword ? 'text' : 'password'}
                                    autoComplete="password"
                                    autoFocus
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
                                확인
                            </Button>
                        </div>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
