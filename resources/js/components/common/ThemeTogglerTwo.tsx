import { useAppearance } from '@/hooks/use-appearance';

export default function ThemeTogglerTwo() {
    const { resolvedAppearance, updateAppearance } = useAppearance();
    const isDark = resolvedAppearance === 'dark';

    return (
        <button
            type="button"
            onClick={() => updateAppearance(isDark ? 'light' : 'dark')}
            className="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-white/10 dark:bg-gray-900 dark:text-white/80 dark:hover:bg-gray-800"
        >
            <span className="h-2 w-2 rounded-full bg-brand-500" />
            <span>{isDark ? 'Dark' : 'Light'}</span>
        </button>
    );
}
