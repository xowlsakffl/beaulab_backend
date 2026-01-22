import AuthLayout from '@/layouts/admin/auth-layout';
import { logout } from '@/routes';
//import { send } from '@/routes/verification';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

export default function VerifyEmail({ status }: { status?: string }) {
    return (
        <AuthLayout>
            <Head title="Email verification" />

            <div className="mb-5 sm:mb-8">
                <h1 className="mb-2 text-title-sm font-semibold text-gray-800 sm:text-title-md dark:text-white/90">
                    이메일 인증
                </h1>
                <p className="text-sm text-gray-500 dark:text-gray-400">
                    이메일로 보낸 인증 링크를 클릭해 인증을 완료해주세요.
                </p>
            </div>

            {status === 'verification-link-sent' && (
                <div className="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm font-medium text-green-700 dark:border-green-200/10 dark:bg-green-700/10 dark:text-green-200">
                    인증 링크를 다시 전송했습니다.
                </div>
            )}

            <Form  className="space-y-4">
                {({ processing }) => (
                    <>
                        <button
                            type="submit"
                            className="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand-500 px-5 py-3 text-sm font-medium text-white transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-70"
                            disabled={processing}
                        >
                            {processing && (
                                <LoaderCircle className="h-4 w-4 animate-spin" />
                            )}
                            인증 이메일 다시 보내기
                        </button>

                        <Link
                            href={logout()}
                            className="block text-center text-sm text-gray-500 transition-colors hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                        >
                            로그아웃
                        </Link>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
