import AuthPageLayout from '@/layouts/admin/auth/auth-page-layout';

export default function AuthLayout({
    children,
    ...props
}: {
    children: React.ReactNode;
}) {
    return (
        <AuthPageLayout {...props}>
            <div className="flex w-full items-center justify-center px-4 py-6 sm:px-8 sm:py-10 lg:w-1/2">
                <div className="w-full max-w-md">
                    <div className="bg-white sm:p-8 dark:bg-gray-900">
                        {children}
                    </div>
                </div>
            </div>
        </AuthPageLayout>
    );
}
