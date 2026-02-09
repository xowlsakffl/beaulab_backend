import { useEffect, useMemo, useRef, useState } from 'react';
import {
    DndContext,
    PointerSensor,
    closestCenter,
    useSensor,
    useSensors,
    type DragEndEvent,
} from '@dnd-kit/core';
import {
    SortableContext,
    useSortable,
    arrayMove,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

import { Card, CardContent } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

import { GripVertical, Image as ImageIcon, UploadCloud, X, Star } from 'lucide-react';

export type MediaValue = Record<string, File[]>;

export type MediaCollectionConfig = {
    key: string;
    label: string;
    multiple: boolean;
    maxFiles?: number;
    accept?: string;
    showRepresentativeBadge?: boolean;
};

type Props = {
    collections: MediaCollectionConfig[];
    value: MediaValue;
    onChange: (next: MediaValue) => void;
    errors?: Record<string, string | undefined>;
    defaultTabKey?: string;
};

function formatBytes(bytes: number) {
    if (!Number.isFinite(bytes)) return '';
    const units = ['Byte', 'KB', 'MB', 'GB'];
    let v = bytes;
    let i = 0;
    while (v >= 1024 && i < units.length - 1) {
        v /= 1024;
        i++;
    }
    return `${v.toFixed(i === 0 ? 0 : 2)} ${units[i]}`;
}

function isImageFile(file: File) {
    return file.type?.startsWith('image/');
}

function useObjectUrl(file: File | null) {
    const [url, setUrl] = useState<string | null>(null);

    useEffect(() => {
        if (!file) return;
        const u = URL.createObjectURL(file);
        setUrl(u);
        return () => URL.revokeObjectURL(u);
    }, [file]);

    return url;
}

function FileThumb({ file }: { file: File }) {
    const url = useObjectUrl(isImageFile(file) ? file : null);

    if (!url) {
        return (
            <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-gray-100 text-gray-500 dark:bg-gray-800">
                <ImageIcon className="h-5 w-5" />
            </div>
        );
    }

    return (
        <img
            src={url}
            alt={file.name}
            className="h-12 w-12 rounded-lg object-cover ring-1 ring-black/5 dark:ring-white/10"
        />
    );
}

function SortableFileRow({
    id,
    file,
    isRepresentative,
    onRemove,
    onMakeRepresentative,
}: {
    id: string;
    file: File;
    isRepresentative: boolean;
    onRemove: () => void;
    onMakeRepresentative?: () => void;
}) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id });

    const style: React.CSSProperties = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.7 : 1,
    };

    return (
        <div
            ref={setNodeRef}
            style={style}
            className="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900"
        >
            <Button
                type="button"
                variant="ghost"
                className="grid h-9 w-9 place-items-center rounded-lg text-gray-400"
                {...attributes}
                {...listeners}
                aria-label="drag"
            >
                <GripVertical className="h-4 w-4" />
            </Button>

            <FileThumb file={file} />

            <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">
                    {file.name}
                </p>
                <p className="text-xs text-gray-500 dark:text-gray-400">
                    {formatBytes(file.size)}
                </p>
            </div>

            <div className="flex items-center gap-1">
                {onMakeRepresentative ? (
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="h-9 w-9"
                        onClick={(e) => {
                            e.preventDefault();
                            onMakeRepresentative();
                        }}
                        title="대표로 설정"
                    >
                        <Star
                            className={[
                                'h-4 w-4',
                                isRepresentative
                                    ? 'fill-yellow-400 text-yellow-500' // ⭐ 내부 채움 + 윤곽선
                                    : 'fill-transparent text-gray-400 hover:text-gray-600 dark:text-gray-500',
                            ].join(' ')}
                        />
                    </Button>
                ) : null}

                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="h-9 w-9 text-gray-500 hover:text-red-600"
                    onClick={(e) => {
                        e.preventDefault();
                        onRemove();
                    }}
                    title="삭제"
                >
                    <X className="h-4 w-4" />
                </Button>
            </div>
        </div>
    );
}


function Dropzone({
    accept,
    multiple,
    disabled,
    onPickFiles,
}: {
    accept?: string;
    multiple: boolean;
    disabled?: boolean;
    onPickFiles: (files: File[]) => void;
}) {
    const [dragOver, setDragOver] = useState(false);

    return (
        <label
            className={[
                'relative block rounded-2xl border border-dashed p-4 transition-all select-none',
                disabled ? 'pointer-events-none opacity-60' : 'cursor-pointer',
                dragOver
                    ? 'border-gray-400 bg-gray-100/80 dark:bg-gray-900/60'
                    : 'border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/40',
            ].join(' ')}
            onDragEnter={(e) => {
                e.preventDefault();
                e.stopPropagation();
                setDragOver(true);
            }}
            onDragOver={(e) => {
                e.preventDefault();
                e.stopPropagation();
                setDragOver(true);
            }}
            onDragLeave={(e) => {
                e.preventDefault();
                e.stopPropagation();
                setDragOver(false);
            }}
            onDrop={(e) => {
                e.preventDefault();
                e.stopPropagation();
                setDragOver(false);
                const files = Array.from(e.dataTransfer.files ?? []);
                if (files.length) onPickFiles(files);
            }}
        >
            <input
                type="file"
                className="sr-only"
                accept={accept}
                multiple={multiple}
                disabled={disabled}
                onChange={(e) => {
                    const files = Array.from(e.currentTarget.files ?? []);
                    e.currentTarget.value = '';
                    if (files.length) onPickFiles(files);
                }}
            />

            {/* 드래그 중 오버레이(블러/효과) */}
            {dragOver ? (
                <div className="pointer-events-none absolute inset-0 rounded-2xl ring-2 ring-brand-500">
                    <div className="absolute inset-0 rounded-2xl bg-white/30 backdrop-blur-[2px] dark:bg-black/20" />
                </div>
            ) : null}

            <div className="flex flex-col items-center gap-2 py-6 text-center">
                <div className="grid h-12 w-12 place-items-center rounded-xl bg-black/5 dark:bg-white/10">
                    <UploadCloud className="h-5 w-5 text-gray-700 dark:text-gray-200" />
                </div>

                <div className="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    파일을 끌어오거나 클릭해서 선택
                </div>

                <div className="text-xs text-gray-500 dark:text-gray-400">
                    {multiple ? '여러 파일 업로드 가능' : '1개 파일만 업로드'}
                </div>
            </div>
        </label>
    );
}

function CollectionPanel({
     c,
     files,
     onSetFiles,
     onAddFiles,
     error,
 }: {
    c: MediaCollectionConfig;
    files: File[];
    onSetFiles: (files: File[]) => void;
    onAddFiles: (incoming: File[]) => void;
    error?: string;
}) {
    const sensors = useSensors(useSensor(PointerSensor, { activationConstraint: { distance: 6 } }));

    const sortableIds = useMemo(
        () => files.map((f, i) => `${c.key}:${f.name}:${f.size}:${f.lastModified}:${i}`),
        [files, c.key],
    );

    const canAddMore = c.multiple
        ? typeof c.maxFiles === 'number'
            ? files.length < c.maxFiles
            : true
        : true;

    const onDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;
        if (!over) return;
        if (active.id === over.id) return;

        const oldIndex = sortableIds.indexOf(String(active.id));
        const newIndex = sortableIds.indexOf(String(over.id));
        if (oldIndex < 0 || newIndex < 0) return;

        onSetFiles(arrayMove(files, oldIndex, newIndex));
    };

    return (
        <div className="space-y-4">
            <div className="space-y-1">
                <Label className="block">
                    <span className="text-sm font-semibold text-gray-900 dark:text-gray-400">
                        {c.label}
                    </span>
                </Label>
            </div>

            <Dropzone
                accept={c.accept ?? 'image/*'}
                multiple={c.multiple}
                disabled={!canAddMore}
                onPickFiles={(picked) => onAddFiles(picked)}
            />

            {/* list */}
            {files.length ? (
                c.multiple ? (
                    <div className="space-y-2">
                        <DndContext
                            sensors={sensors}
                            collisionDetection={closestCenter}
                            onDragEnd={onDragEnd}
                        >
                            <SortableContext
                                items={sortableIds}
                                strategy={verticalListSortingStrategy}
                            >
                                {files.map((file, idx) => (
                                    <SortableFileRow
                                        key={sortableIds[idx]}
                                        id={sortableIds[idx]}
                                        file={file}
                                        isRepresentative={idx === 0}
                                        onRemove={() =>
                                            onSetFiles(
                                                files.filter(
                                                    (_, i) => i !== idx,
                                                ),
                                            )
                                        }
                                        onMakeRepresentative={() => {
                                            if (idx === 0) return;
                                            const next = [
                                                files[idx],
                                                ...files.filter(
                                                    (_, i) => i !== idx,
                                                ),
                                            ];
                                            onSetFiles(next);
                                        }}
                                    />
                                ))}
                            </SortableContext>
                        </DndContext>

                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            드래그로 순서를 바꿀 수 있습니다.
                        </p>
                    </div>
                ) : (
                    <div className="space-y-2">
                        {/* 로고는 1개만 */}
                        {files.slice(0, 1).map((file, idx) => (
                            <div
                                key={`${c.key}:${file.name}:${idx}`}
                                className="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                            >
                                <FileThumb file={file} />
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-semibold text-gray-900 dark:text-gray-200">
                                        {file.name}
                                    </p>
                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                        {formatBytes(file.size)}
                                    </p>
                                </div>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="h-9 w-9 text-gray-500 hover:text-red-600"
                                    onClick={(e) => {
                                        e.preventDefault();
                                        onSetFiles([]);
                                    }}
                                    title="삭제"
                                >
                                    <X className="h-4 w-4" />
                                </Button>
                            </div>
                        ))}
                    </div>
                )
            ) : (
                <div className="flex items-center justify-center gap-2 py-4 text-sm text-gray-500 dark:text-gray-400">
                    <UploadCloud className="h-4 w-4" />
                    업로드된 파일이 없습니다.
                </div>
            )}

            {error ? <p className="text-xs text-error-500">{error}</p> : null}
        </div>
    );
}

export default function EntityMediaUploader({
    collections,
    value,
    onChange,
    errors,
    defaultTabKey,
}: Props) {
    const initialTab = defaultTabKey ?? collections[0]?.key ?? 'media';

    const getFiles = (key: string) => value[key] ?? [];
    const setFiles = (key: string, files: File[]) => onChange({ ...value, [key]: files });

    const addFiles = (c: MediaCollectionConfig, incoming: File[]) => {
        const current = getFiles(c.key);

        if (!c.multiple) {
            // 로고: 새 파일로 교체(1개)
            setFiles(c.key, incoming.slice(0, 1));
            return;
        }

        // 대표/내부 이미지: append + maxFiles 제한
        const merged = [...current, ...incoming];
        const limited = typeof c.maxFiles === 'number' ? merged.slice(0, c.maxFiles) : merged;
        setFiles(c.key, limited);
    };

    return (
        <Card className="w-full">
            <CardContent className="space-y-4">
                <div className="text-md pb-3 font-bold text-gray-800 dark:text-gray-300">
                    <span className="truncate">파일 업로드</span>
                </div>
                {collections.length <= 1 ? (
                    <CollectionPanel
                        c={collections[0]}
                        files={getFiles(collections[0].key)}
                        onSetFiles={(files) =>
                            setFiles(collections[0].key, files)
                        }
                        onAddFiles={(incoming) =>
                            addFiles(collections[0], incoming)
                        }
                        error={errors?.[collections[0].key]}
                    />
                ) : (
                    <Tabs defaultValue={initialTab} className="w-full">
                        <TabsList className="grid w-full grid-cols-2 text-gray-900 dark:text-gray-300">
                            {collections.map((c) => (
                                <TabsTrigger key={c.key} value={c.key}>
                                    {c.label}
                                </TabsTrigger>
                            ))}
                        </TabsList>

                        {collections.map((c) => (
                            <TabsContent
                                key={c.key}
                                value={c.key}
                                className="mt-4"
                            >
                                <CollectionPanel
                                    c={c}
                                    files={getFiles(c.key)}
                                    onSetFiles={(files) =>
                                        setFiles(c.key, files)
                                    }
                                    onAddFiles={(incoming) =>
                                        addFiles(c, incoming)
                                    }
                                    error={errors?.[c.key]}
                                />
                            </TabsContent>
                        ))}
                    </Tabs>
                )}
            </CardContent>
        </Card>
    );
}
