import * as React from 'react';
import * as CheckboxPrimitive from '@radix-ui/react-checkbox';
import { CheckIcon } from 'lucide-react';

import { cn } from '@/lib/utils';

function Checkbox({
                      className,
                      ...props
                  }: React.ComponentProps<typeof CheckboxPrimitive.Root>) {
    return (
        <CheckboxPrimitive.Root
            data-slot="checkbox"
            className={cn(
                [
                    // size/shape
                    'peer size-4 shrink-0 rounded-[4px]',

                    // base colors (TailAdmin-ish)
                    'border border-gray-300 bg-white text-white',
                    'dark:border-gray-700 dark:bg-gray-950',

                    // checked state -> brand
                    'data-[state=checked]:border-brand-500 data-[state=checked]:bg-brand-500',

                    // focus ring
                    'outline-none focus-visible:ring-4 focus-visible:ring-brand-500/20',
                    'dark:focus-visible:ring-brand-500/30',

                    // disabled
                    'disabled:cursor-not-allowed disabled:opacity-50',

                    // a11y invalid
                    'aria-invalid:border-error-500 aria-invalid:ring-4 aria-invalid:ring-error-500/20',
                    'dark:aria-invalid:ring-error-500/30',
                ].join(' '),
                className,
            )}
            {...props}
        >
            <CheckboxPrimitive.Indicator
                data-slot="checkbox-indicator"
                className="flex items-center justify-center text-current"
            >
                <CheckIcon className="size-3.5" />
            </CheckboxPrimitive.Indicator>
        </CheckboxPrimitive.Root>
    );
}

export { Checkbox };
