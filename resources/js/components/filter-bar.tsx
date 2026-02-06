import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import React, { useEffect, useRef, useState } from 'react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export type FilterOption<V extends string | number> = {
    label: string;
    value: V;
};

type BaseField<TFilters extends Record<string, unknown>> = {
    key: keyof TFilters & string;
    label?: React.ReactNode;
    className?: string;
    disabled?: boolean;
};

export type TextField<TFilters extends Record<string, unknown>> =
    BaseField<TFilters> & {
        type: 'text';
        placeholder?: string;
        debounceMs?: number; // 기본 300
        normalize?: (value: string) => string;
    };

export type SelectField<
    TFilters extends Record<string, unknown>,
    V extends string | number,
> = BaseField<TFilters> & {
    type: 'select';
    placeholder?: string; // "전체" 같은 의미
    options: FilterOption<V>[];

    /**
     * nullValue: 선택 해제(전체) 값
     * - any 금지라서 "필터 값 타입"으로 받는다.
     */
    nullValue?: TFilters[keyof TFilters & string];
};

export type FilterField<TFilters extends Record<string, unknown>> =
    | TextField<TFilters>
    | SelectField<TFilters, string | number>;

export type FilterBarProps<TFilters extends Record<string, unknown>> = {
    value: TFilters;
    fields: FilterField<TFilters>[];

    disabled?: boolean;

    onChange: (next: Partial<TFilters>) => void;

    onReset?: () => void;
    rightActions?: React.ReactNode;

    isDirty?: (value: TFilters) => boolean;
};

function defaultIsDirty<T extends Record<string, unknown>>(v: T) {
    return Object.values(v).some((x) => {
        if (x === null || x === undefined) return false;
        if (typeof x === 'string') return x.trim() !== '';
        return true;
    });
}

export default function FilterBar<TFilters extends Record<string, unknown>>({
    value,
    fields,
    disabled = false,
    onChange,
    onReset,
    isDirty,
}: FilterBarProps<TFilters>) {
    const dirty = (isDirty ?? defaultIsDirty)(value);

    return (
        <div className="flex flex-col gap-3 rounded-xl border p-4 dark:border-white/[0.05] dark:bg-white/[0.03]">
            <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:flex lg:flex-wrap lg:items-end">
                    {fields.map((f) => (
                        <FilterFieldRenderer<TFilters>
                            key={f.key}
                            field={f}
                            value={value}
                            disabled={Boolean(f.disabled)}
                            onChange={onChange}
                        />
                    ))}
                </div>

                <div className="flex items-center justify-end gap-2">
                    {onReset ? (
                        <Button
                            type="button"
                            variant="ghost"
                            disabled={disabled || !dirty}
                            onClick={onReset}
                            className="py-6 text-brand-500    dark:text-gray-200 "
                        >
                            필터 초기화
                        </Button>
                    ) : null}
                </div>
            </div>
        </div>
    );
}

function FilterFieldRenderer<TFilters extends Record<string, unknown>>({
    field,
    value,
    disabled,
    onChange,
}: {
    field: FilterField<TFilters>;
    value: TFilters;
    disabled: boolean;
    onChange: (next: Partial<TFilters>) => void;
}) {
    switch (field.type) {
        case 'text':
            return (
                <TextFieldInput<TFilters>
                    field={field}
                    value={value}
                    disabled={disabled}
                    onChange={onChange}
                />
            );

        case 'select':
            return (
                <SelectFieldInput<TFilters>
                    field={field}
                    value={value}
                    disabled={disabled}
                    onChange={onChange}
                />
            );

        default:
            return null;
    }
}

function TextFieldInput<TFilters extends Record<string, unknown>>({
    field,
    value,
    disabled,
    onChange,
}: {
    field: TextField<TFilters>;
    value: TFilters;
    disabled: boolean;
    onChange: (next: Partial<TFilters>) => void;
}) {
    const key = field.key;

    // 서버/URL 기준 값 (확정값)
    const committed = (value[key] ?? '') as string;

    // 사용자가 입력 중인 값
    const [draft, setDraft] = React.useState<string>(committed);

    const inputRef = React.useRef<HTMLInputElement | null>(null);
    const timer = React.useRef<number | null>(null);
    const debounceMs = field.debounceMs ?? 300;

    const emit = React.useCallback(
        (v: string) => {
            const normalized = field.normalize ? field.normalize(v) : v;
            onChange({ [key]: normalized } as Partial<TFilters>);
        },
        [field, key, onChange],
    );

    /**
     * 서버값 동기화 규칙
     * - 입력 중이면(draft !== committed) 절대 덮지 않음
     * - 입력 안 하고 있을 때만 동기화
     */
    React.useEffect(() => {
        if (draft !== committed) return;
        setDraft(committed);
    }, [committed]); // eslint-disable-line react-hooks/exhaustive-deps

    const onLocalChange = (v: string) => {
        setDraft(v);

        if (timer.current) window.clearTimeout(timer.current);
        timer.current = window.setTimeout(() => emit(v), debounceMs);
    };

    return (
        <div className={field.className ?? 'w-full lg:w-64'}>
            {field.label && (
                <div className="mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">
                    {field.label}
                </div>
            )}

            <Input
                ref={inputRef}
                value={draft}
                disabled={disabled}
                placeholder={field.placeholder}
                onChange={(e) => onLocalChange(e.target.value)}
                onBlur={() => {
                    // blur 시점에 서버값과 다르면 확정
                    if (draft !== committed) {
                        emit(draft);
                    }
                }}
                onKeyDown={(e) => {
                    if (e.key === 'Enter') {
                        if (timer.current) window.clearTimeout(timer.current);
                        emit(draft);
                    }
                }}
            />
        </div>
    );
}


function SelectFieldInput<TFilters extends Record<string, unknown>>({
    field,
    value,
    disabled,
    onChange,
}: {
    field: SelectField<TFilters, string | number>;
    value: TFilters;
    disabled: boolean;
    onChange: (next: Partial<TFilters>) => void;
}) {
    const key = field.key;
    const current = value[key];

    const nullValue = field.nullValue ?? null;

    const currentString =
        current === null || current === undefined ? '' : String(current);

    return (
        <div className={field.className ?? 'w-full lg:w-52'}>
            {field.label ? (
                <div className="mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">
                    {field.label}
                </div>
            ) : null}

            <Select
                value={currentString}
                disabled={disabled}
                onValueChange={(v) => {
                    if (v === '') {
                        onChange({ [key]: nullValue } as Partial<TFilters>);
                        return;
                    }

                    // string | number 원래 타입 복원
                    const found = field.options.find(
                        (o) => String(o.value) === v,
                    );

                    onChange({
                        [key]: found ? found.value : nullValue,
                    } as Partial<TFilters>);
                }}
            >
                <SelectTrigger>
                    <SelectValue placeholder={field.placeholder ?? '전체'} />
                </SelectTrigger>

                <SelectContent>
                    {field.options.map((o) => (
                        <SelectItem
                            key={String(o.value)}
                            value={String(o.value)}
                        >
                            {o.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}

