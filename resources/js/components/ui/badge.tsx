import * as React from "react";
import { Slot } from "@radix-ui/react-slot";
import { cva, type VariantProps } from "class-variance-authority";
import { cn } from "@/lib/utils";

export type BadgeVariant = "light" | "solid";
export type BadgeSize = "sm" | "md";
export type BadgeColor =
    | "primary"
    | "success"
    | "error"
    | "warning"
    | "info"
    | "light"
    | "dark";

const badgeVariants = cva(
    "inline-flex items-center justify-center whitespace-nowrap font-medium rounded-full",
    {
        variants: {
            variant: {
                light: "",
                solid: "",
            },
            size: {
                sm: "px-2 py-0.5 text-theme-xs",
                md: "px-2.5 py-0.5 text-sm",
            },
            color: {
                primary: "",
                success: "",
                error: "",
                warning: "",
                info: "",
                light: "",
                dark: "",
            },
        },
        compoundVariants: [
            // light
            { variant: "light", color: "primary", className: "bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400" },
            { variant: "light", color: "success", className: "bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500" },
            { variant: "light", color: "error", className: "bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500" },
            { variant: "light", color: "warning", className: "bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-orange-400" },
            { variant: "light", color: "info", className: "bg-blue-light-50 text-blue-light-500 dark:bg-blue-light-500/15 dark:text-blue-light-500" },
            { variant: "light", color: "light", className: "bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-white/80" },
            { variant: "light", color: "dark", className: "bg-gray-500 text-white dark:bg-white/5 dark:text-white" },

            // solid
            { variant: "solid", color: "primary", className: "bg-brand-500 text-white" },
            { variant: "solid", color: "success", className: "bg-success-500 text-white" },
            { variant: "solid", color: "error", className: "bg-error-500 text-white" },
            { variant: "solid", color: "warning", className: "bg-warning-500 text-white" },
            { variant: "solid", color: "info", className: "bg-blue-light-500 text-white" },
            { variant: "solid", color: "light", className: "bg-gray-400 text-white dark:bg-white/5 dark:text-white/80" },
            { variant: "solid", color: "dark", className: "bg-gray-700 text-white" },
        ],
        defaultVariants: {
            variant: "light",
            size: "md",
            color: "primary",
        },
    }
);

type SpanPropsWithoutColor = Omit<React.HTMLAttributes<HTMLSpanElement>, "color">;

export interface BadgeProps
    extends SpanPropsWithoutColor,
        VariantProps<typeof badgeVariants> {
    asChild?: boolean;
    startIcon?: React.ReactNode;
    endIcon?: React.ReactNode;
}

function Badge({
                   className,
                   variant,
                   size,
                   color,
                   startIcon,
                   endIcon,
                   asChild = false,
                   children,
                   ...props
               }: BadgeProps) {
    const Comp = asChild ? Slot : "span";

    return (
        <Comp
            data-slot="badge"
            className={cn(
                "inline-flex items-center gap-1",
                badgeVariants({ variant, size, color }),
                className
            )}
            {...props}
        >
            {startIcon ? <span className="shrink-0">{startIcon}</span> : null}
            {children}
            {endIcon ? <span className="shrink-0">{endIcon}</span> : null}
        </Comp>
    );
}

export { Badge, badgeVariants };
