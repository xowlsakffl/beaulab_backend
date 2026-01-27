import { cn } from '@/lib/utils';

export default function SidebarOverlay({
    open,
    onClose,
    offsetTopClassName,
}: {
    open: boolean;
    onClose: () => void;
    offsetTopClassName?: string;
}) {
    if (!open) return null;

    return (
        <div
            className={cn(
                'fixed right-0 bottom-0 left-0 z-40 bg-black/55 dark:bg-black/65',
                offsetTopClassName ?? 'top-0',
            )}
            onPointerDown={onClose}
        />
    );
}
