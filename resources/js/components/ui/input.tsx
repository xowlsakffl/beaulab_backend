import * as React from "react";
import { cn } from "@/lib/utils";

interface InputProps extends React.ComponentProps<"input"> {
    error?: boolean;
}

function Input({ className, type, error, ...props }: InputProps) {
    return (
        <input
            type={type}
            data-slot="input"
            className={cn(
                // base
                'h-11 w-full rounded-lg border bg-white px-4 text-sm text-gray-900 outline-none',

                // normal
                'border-gray-200 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10',

                // dark
                'dark:border-gray-800 dark:bg-gray-950 dark:text-white/90 dark:focus:ring-brand-500/20',

                // error
                error &&
                'border-error-500 focus:border-error-500 focus:ring-error-500/10 dark:focus:ring-error-500/20',

                className
            )}
            {...props}
        />
    );
}

export { Input };
