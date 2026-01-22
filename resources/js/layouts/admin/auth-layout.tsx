import TailAdminAuthPageLayout from '@/layouts/admin/auth/TailAdminAuthPageLayout';

export default function AuthLayout({
    children,
    ...props
}: {
    children: React.ReactNode;
}) {
    return (
        <TailAdminAuthPageLayout {...props}>
            <div className="flex w-full items-center justify-center px-6 py-10 sm:px-10 lg:w-1/2">
                <div className="w-full max-w-md">
                    <div className="bg-white p-6 sm:p-8 dark:bg-gray-900">
                        {children}
                    </div>
                </div>
            </div>
        </TailAdminAuthPageLayout>
    );
}
