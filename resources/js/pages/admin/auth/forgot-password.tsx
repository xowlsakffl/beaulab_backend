// Components
import InputError from '@/components/input-error';
import { ChevronLeftIcon } from '@/icons';
import AuthLayout from '@/layouts/admin/auth-layout';
import { login } from '@/routes';
import { email } from '@/routes/password';
import { Form, Head, Link } from '@inertiajs/react';
import { CheckCircle2, LoaderCircle } from 'lucide-react';
import { Alert, AlertTitle } from '@/components/ui/alert';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

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
                <Alert variant="success">
                    <CheckCircle2 />
                    <AlertTitle>{status}</AlertTitle>
                </Alert>
            )}

            <Form {...email.form()} className="space-y-6">
                {({ processing, errors }) => (
                    <>
                        <div>
                            <Label className="mb-1.5 block text-sm">
                                <span className="text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                    이메일
                                </span>
                                <span className="text-error-500">*</span>
                            </Label>

                            <Input
                                name="email"
                                type="email"
                                autoFocus
                                tabIndex={1}
                                autoComplete="email"
                                placeholder="email@example.com"
                                error={!!errors.email}
                            />

                            <InputError
                                message={errors.email}
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
                            data-test="email-password-reset-link-button"
                        >
                            {processing && (
                                <LoaderCircle className="h-4 w-4 animate-spin" />
                            )}
                            재설정 링크 보내기
                        </Button>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
