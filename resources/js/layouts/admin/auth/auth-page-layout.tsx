import GridShape from '@/components/grid-shape';
import ThemeTogglerTwo from '@/components/theme-toggler-two';
import { login } from '@/routes';
import { Link } from '@inertiajs/react';

export default function AuthPageLayout({
                                           children,
                                       }: {
    children: React.ReactNode;
}) {
    return (
        <div className="relative z-1 bg-white sm:p-0 dark:bg-gray-900">
            <div className="relative flex h-screen w-full flex-col justify-center sm:p-0 lg:flex-row dark:bg-gray-900">
                {children}

                <div className="hidden h-full w-full items-center bg-brand-500 lg:grid lg:w-1/2 dark:bg-white/5">
                    <div className="relative z-1 flex items-center justify-center">
                        {/* <!-- ===== Common Grid Shape Start ===== --> */}
                        <GridShape />

                        <div className="flex max-w-xs flex-col items-center">
                            <Link href={login()} className="mb-4 block">
                                <img width={231} height={48} src="#" alt="Logo" />
                            </Link>

                            <p className="text-center text-white dark:text-white/60">
                                뷰랩 문구
                            </p>
                        </div>
                    </div>
                </div>

                <div className="fixed right-3 bottom-3 z-50 sm:right-4 sm:bottom-4">
                    <ThemeTogglerTwo />
                </div>
            </div>
        </div>
    );
}
