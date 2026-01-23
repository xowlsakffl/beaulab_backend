import { Button } from '@/components/ui/button';

import { useAppearance } from '@/hooks/use-appearance';
import { Moon, Sun } from 'lucide-react';
import { HTMLAttributes, useEffect } from 'react';

export default function AppearanceToggle({
    className = '',
    ...props
}: HTMLAttributes<HTMLDivElement>) {
    const { appearance, updateAppearance } = useAppearance();

    // 기본값은 light로 강제 (system 등으로 들어와도 light로 맞춤)
    useEffect(() => {
        if (appearance !== 'light' && appearance !== 'dark') {
            updateAppearance('light');
        }
    }, [appearance, updateAppearance]);

    const isDark = appearance === 'dark';

    return (
        <div className={className} {...props}>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                onClick={() => updateAppearance(isDark ? 'light' : 'dark')}
                className={[
                    'h-10 w-10 rounded-full border border-gray-200 bg-white',
                    'text-gray-700 hover:bg-gray-50',
                    'dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-white/5',
                ].join(' ')}
            >
                {isDark ? (
                    <Moon className="h-5 w-5" />
                ) : (
                    <Sun className="h-5 w-5" />
                )}
                <span className="sr-only">Toggle theme</span>
            </Button>
        </div>
    );
}
