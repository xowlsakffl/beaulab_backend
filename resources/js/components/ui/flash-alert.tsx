import { usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { Alert, AlertTitle } from '@/components/ui/alert';
import { CheckCircle2, AlertCircle, Info, X } from 'lucide-react';
import { Button } from '@/components/ui/button';

type Flash = {
    type?: 'success' | 'error' | 'info' | 'warning';
    message?: string | null;
};

type PageProps = {
    flash?: Flash;
};

type Normalized = {
    variant: 'success' | 'destructive' | 'default';
    type: 'success' | 'error' | 'info' | 'warning';
    message: string;
    Icon: React.ComponentType<{ className?: string }>;
};

function normalize(flash?: Flash): Normalized | null {
    if (!flash?.message) return null;

    const type = flash.type ?? 'info';

    if (type === 'success') {
        return {
            variant: 'success',
            type,
            message: flash.message,
            Icon: CheckCircle2,
        };
    }

    if (type === 'error') {
        return {
            variant: 'destructive',
            type,
            message: flash.message,
            Icon: AlertCircle,
        };
    }

    // info / warning → default
    return {
        variant: 'default',
        type,
        message: flash.message,
        Icon: Info,
    };
}

export default function FlashAlert() {
    const { flash } = usePage<PageProps>().props;

    const incoming = useMemo(
        () => normalize(flash),
        [flash?.type, flash?.message],
    );

    // flash가 사라져도 UI 유지
    const [current, setCurrent] = useState<Normalized | null>(null);

    useEffect(() => {
        if (!incoming) return;
        setCurrent(incoming);
    }, [incoming?.message, incoming?.type]);

    if (!current) return null;

    const Icon = current.Icon;

    return (
        <Alert variant={current.variant} className="mb-0 items-center py-1">
            <Icon
                className={
                    current.type === 'error'
                        ? 'text-red-600'
                        : undefined
                }
            />

            <AlertTitle className="flex w-full items-center justify-between gap-3 break-words">
            <span className="min-w-0 flex-1">
              {current.message}
            </span>

                <Button
                    type="button"
                    variant="ghost"
                    onClick={() => setCurrent(null)}
                    className="shrink-0 rounded-md px-2 py-1 text-xs opacity-80 hover:opacity-100"
                >
                    <X />
                </Button>
            </AlertTitle>
        </Alert>
    );
}
