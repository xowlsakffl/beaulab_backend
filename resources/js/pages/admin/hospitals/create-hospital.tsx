import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

import AppLayout from '@/layouts/admin/app-layout';
import { openKakaoAddressSearch } from '@/lib/kakao-address';
import { dashboard } from '@/routes/admin';
import hospitals from '@/routes/admin/hospitals';
import type { BreadcrumbItem } from '@/types';

import { useForm } from '@inertiajs/react';
import { LoaderCircle, MapPin } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';

import EntityMediaUploader, {
    type MediaValue,
} from '@/components/media-uploader';

const breadcrumbs: BreadcrumbItem[] = [
    { title: '홈', href: dashboard().url },
    { title: '병원 관리', href: hospitals.indexHospitalPageForStaff().url },
    { title: '병원 등록', href: hospitals.createHospitalForStaff().url },
];

type Form = {
    name: string;

    description: string;
    consulting_hours: string;
    direction: string;

    address: string;
    address_detail: string;

    latitude: string;
    longitude: string;

    tel: string;
    email: string;

    // 파일(서버에서 logo: File|null, gallery: File[]로 받는다고 가정)
    logo: any | null;
    gallery: any[];

    // activate_now: boolean;
};

const mediaCollections = [
    { key: 'logo', label: '로고', multiple: false, maxFiles: 1 },
    {
        key: 'gallery',
        label: '대표/내부 이미지',
        multiple: true,
        maxFiles: 12,
        showRepresentativeBadge: true,
    },
] as const;

function CreateHospital() {
    const { data, setData, post, processing, errors, clearErrors } =
        useForm<Form>({
            name: '',

            description: '',
            consulting_hours: '',
            direction: '',

            address: '',
            address_detail: '',

            latitude: '',
            longitude: '',

            tel: '',
            email: '',

            logo: null,
            gallery: [],

            // activate_now: false,
        });

    // 공용 미디어 상태(FilePond ActualFileObject[] 기반)
    const [media, setMedia] = useState<MediaValue>({
        logo: [],
        gallery: [],
    });

    // input/textarea 공통 바인딩
    const bindField = <K extends keyof Form>(key: K) => ({
        name: String(key),
        value: data[key] as any,
        onChange: (
            e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>,
        ) => {
            setData(key, e.currentTarget.value as any);
            if ((errors as any)[key]) clearErrors(key as any);
        },
    });

    const focusFirstError = (errs: Record<string, string>) => {
        const firstKey = Object.keys(errs)[0];
        if (!firstKey) return;

        const el = document.querySelector(
            `[name="${CSS.escape(firstKey)}"]`,
        ) as HTMLElement | null;

        el?.scrollIntoView?.({ block: 'center', behavior: 'smooth' });
        el?.focus?.();
    };

    function submit(e: React.FormEvent) {
        e.preventDefault();

        setData('logo', media.logo?.[0] ?? null);
        setData('gallery', media.gallery ?? []);

        post(hospitals.storeHospitalForStaff().url, {
            preserveScroll: true,
            forceFormData: true,
            onError: (errs) => focusFirstError(errs),
        });
    }

    const onClickAddressSearch = async () => {
        try {
            const r = await openKakaoAddressSearch();

            setData('address', r.address);
            setData('latitude', r.latitude);
            setData('longitude', r.longitude);

            clearErrors(['address', 'latitude', 'longitude'] as any);
        } catch (e: any) {
            alert(e?.message ?? '주소 검색에 실패했습니다.');
        }
    };

    return (
        <div className="mx-auto w-full max-w-5xl">
            <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_360px] lg:items-start">
                {/* 좌측: 폼 */}
                <Card className="w-full">
                    <CardContent className="px-2 lg:px-6">
                        <div className="text-md pb-3 font-bold text-gray-800 dark:text-gray-300">
                            <span className="truncate">병원 정보 입력</span>
                        </div>

                        <form onSubmit={submit} className="space-y-4">
                            {/* 병원명 */}
                            <div className="space-y-2">
                                <Label className="block" htmlFor="name">
                                    <span className="text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                        병원명
                                    </span>{' '}
                                    <span className="text-error-500">*</span>
                                </Label>

                                <Input
                                    id="name"
                                    placeholder="병원명을 입력하세요."
                                    autoComplete="organization"
                                    error={!!errors.name}
                                    {...bindField('name')}
                                />

                                {errors.name ? (
                                    <p className="text-error-500 text-xs">
                                        {errors.name}
                                    </p>
                                ) : null}
                            </div>

                            {/* 대표 번호 */}
                            <div className="space-y-2">
                                <Label className="block" htmlFor="tel">
                                    <span className="text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                        대표 번호
                                    </span>
                                </Label>

                                <Input
                                    id="tel"
                                    placeholder="예) 02-1234-5678"
                                    autoComplete="tel"
                                    error={!!errors.tel}
                                    {...bindField('tel')}
                                />

                                {errors.tel ? (
                                    <p className="text-error-500 text-xs">
                                        {errors.tel}
                                    </p>
                                ) : null}
                            </div>

                            {/* 대표 이메일 */}
                            <div className="space-y-2">
                                <Label className="block" htmlFor="email">
                                    <span className="text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                        대표 이메일
                                    </span>
                                </Label>

                                <Input
                                    id="email"
                                    type="email"
                                    placeholder="예) hello@beaulab.co"
                                    autoComplete="email"
                                    error={!!errors.email}
                                    {...bindField('email')}
                                />

                                {errors.email ? (
                                    <p className="text-error-500 text-xs">
                                        {errors.email}
                                    </p>
                                ) : null}
                            </div>

                            {/* 주소 (직접 입력 금지) */}
                            <div className="space-y-2">
                                <Label className="block" htmlFor="address">
                                    <span className="text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                        주소
                                    </span>
                                </Label>

                                <div className="flex gap-2">
                                    <Input
                                        name="address"
                                        value={data.address}
                                        readOnly
                                        onClick={onClickAddressSearch}
                                        placeholder="주소 검색 버튼을 눌러 선택하세요."
                                        autoComplete="off"
                                        error={!!errors.address}
                                        className="flex-1 cursor-pointer"
                                    />

                                    <Button
                                        type="button"
                                        variant="brand"
                                        className="shrink-0 py-5"
                                        onClick={onClickAddressSearch}
                                    >
                                        <MapPin className="h-4 w-4" />
                                        검색
                                    </Button>
                                </div>

                                {errors.address ? (
                                    <p className="text-error-500 text-xs">
                                        {errors.address}
                                    </p>
                                ) : null}

                                <Input
                                    id="address_detail"
                                    placeholder="상세 주소 (예: 3층, 301호)"
                                    autoComplete="address-line2"
                                    error={!!errors.address_detail}
                                    {...bindField('address_detail')}
                                />

                                {errors.address_detail ? (
                                    <p className="text-error-500 text-xs">
                                        {errors.address_detail}
                                    </p>
                                ) : null}
                            </div>

                            {/* 병원 소개 */}
                            <div className="space-y-2">
                                <Label className="block" htmlFor="description">
                                    <span className="text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                        병원 소개
                                    </span>
                                </Label>

                                <Textarea
                                    id="description"
                                    placeholder="간단 소개를 입력하세요."
                                    error={!!errors.description}
                                    {...bindField('description')}
                                />

                                {errors.description ? (
                                    <p className="text-error-500 text-xs">
                                        {errors.description}
                                    </p>
                                ) : null}
                            </div>

                            {/* 운영 시간 */}
                            <div className="space-y-2">
                                <Label
                                    className="block"
                                    htmlFor="consulting_hours"
                                >
                                    <span className="text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                        운영 시간
                                    </span>
                                </Label>

                                <Textarea
                                    id="consulting_hours"
                                    placeholder="예) 평일 10:00~19:00 / 토 10:00~15:00"
                                    error={!!errors.consulting_hours}
                                    {...bindField('consulting_hours')}
                                />

                                {errors.consulting_hours ? (
                                    <p className="text-error-500 text-xs">
                                        {errors.consulting_hours}
                                    </p>
                                ) : null}
                            </div>

                            {/* 오시는 길 */}
                            <div className="space-y-2">
                                <Label className="block" htmlFor="direction">
                                    <span className="text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                        오시는 길
                                    </span>
                                </Label>

                                <Textarea
                                    id="direction"
                                    placeholder="예) 2호선 홍대입구역 3번 출구 도보 5분"
                                    error={!!errors.direction}
                                    {...bindField('direction')}
                                />

                                {errors.direction ? (
                                    <p className="text-error-500 text-xs">
                                        {errors.direction}
                                    </p>
                                ) : null}
                            </div>

                            {/* 옵션 */}
                            <div className="pt-1">
                                <Label className="flex items-center gap-3">
                                    <Checkbox
                                    // checked={data.activate_now}
                                    // onCheckedChange={(v) => setData('activate_now', Boolean(v))}
                                    />
                                    <span className="text-theme-sm font-normal text-gray-700 dark:text-gray-400">
                                        등록 후 바로 활성화
                                    </span>
                                </Label>
                            </div>

                            {/* 제출 */}
                            <div className="pt-2">
                                <Button
                                    type="submit"
                                    variant="brand"
                                    disabled={processing}
                                    className="w-full"
                                >
                                    {processing ? (
                                        <LoaderCircle className="h-4 w-4 animate-spin" />
                                    ) : null}
                                    병원 등록
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* 우측: 업로드 카드(공용) */}
                <EntityMediaUploader
                    collections={mediaCollections as any}
                    value={media}
                    onChange={setMedia}
                />
            </div>
        </div>
    );
}

CreateHospital.layout = (page: ReactNode) => (
    <AppLayout breadcrumbs={breadcrumbs}>{page}</AppLayout>
);

export default CreateHospital;
