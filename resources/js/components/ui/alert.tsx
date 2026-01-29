import * as React from "react"
import { cva, type VariantProps } from "class-variance-authority"
import { cn } from "@/lib/utils"

const alertVariants = cva(
    [
        // layout (아이콘 있으면 2컬럼)
        "relative w-full rounded-lg border px-4 py-3 text-sm mb-3",
        "grid has-[>svg]:grid-cols-[16px_1fr] grid-cols-[0_1fr]",
        "has-[>svg]:gap-x-3 gap-y-0.5 items-start",
        // icon
        "[&>svg]:size-4 [&>svg]:translate-y-0.5 [&>svg]:text-current",
    ].join(" "),
    {
        variants: {
            variant: {
                // 기본
                default: "bg-background text-foreground border-border",

                success: [
                    "border-success-500 bg-success-50 text-success-900",
                    "dark:border-emerald-900/40 dark:bg-success-950/40 dark:text-gray-300",
                    // description 톤
                    "*:data-[slot=alert-description]:text-success-800/90 dark:*:data-[slot=alert-description]:text-success-100/80",
                ].join(" "),

                destructive: [
                    "border-red-200 bg-red-50 text-red-900",
                    "dark:border-red-900/40 dark:bg-red-950/40 dark:text-red-50",
                    "*:data-[slot=alert-description]:text-red-800/90 dark:*:data-[slot=alert-description]:text-red-100/80",
                ].join(" "),
            },
        },
        defaultVariants: {
            variant: "default",
        },
    }
)

function Alert({
                   className,
                   variant,
                   ...props
               }: React.ComponentProps<"div"> & VariantProps<typeof alertVariants>) {
    return (
        <div
            data-slot="alert"
            role="alert"
            className={cn(alertVariants({ variant }), className)}
            {...props}
        />
    )
}

function AlertTitle({ className, ...props }: React.ComponentProps<"div">) {
    return (
        <div
            data-slot="alert-title"
            className={cn(
                "col-start-2 min-h-4 font-semibold tracking-tight",
                className
            )}
            {...props}
        />
    )
}

function AlertDescription({ className, ...props }: React.ComponentProps<"div">) {
    return (
        <div
            data-slot="alert-description"
            className={cn(
                "col-start-2 grid justify-items-start gap-1 text-sm [&_p]:leading-relaxed",
                className
            )}
            {...props}
        />
    )
}

export { Alert, AlertTitle, AlertDescription }
