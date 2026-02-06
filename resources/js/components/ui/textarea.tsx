import { cn } from '@/lib/utils';
import * as React from 'react';

interface TextareaProps extends React.ComponentProps<'textarea'> {
    error?: boolean;
}

function Textarea({ className, error, ...props }: TextareaProps) {
    return (
        <textarea
            data-slot="textarea"
            className={cn(
                // base
                'min-h-[96px] w-full resize-y rounded-lg border bg-white px-4 py-3 text-sm text-gray-900 outline-none',

                // normal
                'border-gray-200 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10',

                // dark
                'dark:border-gray-800 dark:bg-gray-950 dark:text-white/90 dark:focus:ring-brand-500/20',

                // error
                error &&
                    'border-error-500 focus:border-error-500 focus:ring-error-500/10 dark:focus:ring-error-500/20',

                className,
            )}
            {...props}
        />
    );
}

export { Textarea };
