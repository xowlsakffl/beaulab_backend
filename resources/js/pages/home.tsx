import { login as adminLogin } from '@/routes';
import { Head, Link } from '@inertiajs/react';

export default function Home() {
    return (
        <div className="min-h-dvh bg-white text-gray-900 dark:bg-gray-950 dark:text-white/90">
            <Head title="뷰랩" />

            <div className="mx-auto max-w-4xl px-6 py-16">
                <h1 className="text-3xl font-semibold tracking-tight">
                    Beaulab Home
                </h1>
                <p className="mt-3 text-gray-600 dark:text-white/60">
                    모든 사용자가 접근 가능한 공개 페이지입니다.
                </p>

                <div className="mt-8 flex flex-wrap gap-3">
                    <Link
                        href={adminLogin()}
                        className="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-3 text-sm font-medium text-white hover:bg-brand-600"
                    >
                        관리자 로그인
                    </Link>
                </div>
            </div>
        </div>
    );
}
