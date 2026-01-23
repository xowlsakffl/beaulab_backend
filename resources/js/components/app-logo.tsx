import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-10 items-center justify-center rounded-lg bg-brand-500 text-white shadow-sm">
                <AppLogoIcon className="size-5 text-white" />
            </div>
            <div className="ml-2 grid flex-1 text-left text-sm leading-tight">
                <span className="truncate font-semibold text-gray-900 dark:text-white/90">
                    Beaulab
                </span>
                <span className="truncate text-xs text-gray-500 dark:text-gray-400">
                    Admin
                </span>
            </div>
        </>
    );
}
