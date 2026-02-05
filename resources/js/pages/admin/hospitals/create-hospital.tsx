import AppLayout from '@/layouts/admin/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes/admin';
import hospitals from '@/routes/admin/hospitals';
import type { BreadcrumbItem } from '@/types';
import { useForm } from '@inertiajs/react';
import { LoaderCircle, MapPin } from 'lucide-react';
import type { ReactNode } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { openKakaoAddressSearch } from '@/lib/kakao-address';

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

    latitude: string;   // 서버 filter가 string으로 캐스팅하니 문자열로 관리 추천
    longitude: string;

    tel: string;
    email: string;

    //activate_now:boolean;
};


function CreateHospital() {
    const { data, setData, post, processing, errors, clearErrors } = useForm<Form>({
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

        //activate_now: false,
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        clearErrors();

        post('/admin/api/hospitals', {
            preserveScroll: true,
        });
    }

    const onClickAddressSearch = async () => {
        try {
            const r = await openKakaoAddressSearch();
            setData('address', r.address);
            setData('latitude', r.latitude);
            setData('longitude', r.longitude);
        } catch (e: any) {
            // 취소/실패 처리
            alert(e?.message ?? '주소 검색에 실패했습니다.');
        }
    };

    return (
        <Card className="mx-auto w-full max-w-md">
            <CardContent className="p-4 lg:p-6">
                <form onSubmit={submit} className="space-y-4">
                    {/* 병원명 (required) */}
                    <div className="space-y-2">
                        <Label className="block">
                            <span className="text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                병원명
                            </span>{' '}
                            <span className="text-error-500">*</span>
                        </Label>

                        <Input
                            name="name"
                            value={data.name}
                            onChange={(e) =>
                                setData('name', e.currentTarget.value)
                            }
                            placeholder="병원명을 입력하세요."
                            autoComplete="organization"
                            error={!!errors.name}
                        />

                        {errors.name ? (
                            <p className="text-xs text-error-500">
                                {errors.name}
                            </p>
                        ) : null}
                    </div>

                    {/* 연락처 (nullable) */}
                    <div className="space-y-2">
                        <Label className="block">
                            <span className="text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                연락처
                            </span>
                        </Label>

                        <Input
                            name="tel"
                            value={data.tel}
                            onChange={(e) =>
                                setData('tel', e.currentTarget.value)
                            }
                            placeholder="예) 02-1234-5678"
                            autoComplete="tel"
                            error={!!errors.tel}
                        />

                        {errors.tel ? (
                            <p className="text-xs text-error-500">
                                {errors.tel}
                            </p>
                        ) : null}
                    </div>

                    {/* 이메일 (nullable) */}
                    <div className="space-y-2">
                        <Label className="block">
                            <span className="text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                이메일
                            </span>
                        </Label>

                        <Input
                            name="email"
                            type="email"
                            value={data.email}
                            onChange={(e) =>
                                setData('email', e.currentTarget.value)
                            }
                            placeholder="예) hello@beaulab.co"
                            autoComplete="email"
                            error={!!errors.email}
                        />

                        {errors.email ? (
                            <p className="text-xs text-error-500">
                                {errors.email}
                            </p>
                        ) : null}
                    </div>

                    {/* 주소 */}
                    <div className="space-y-2">
                        <Label className="block">
                            <span className="text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                주소
                            </span>
                        </Label>

                        <div className="flex gap-2">
                            <Input
                                name="address"
                                value={data.address}
                                onChange={(e) =>
                                    setData('address', e.currentTarget.value)
                                }
                                placeholder="주소를 검색하거나 입력하세요."
                                autoComplete="street-address"
                                error={!!errors.address}
                                className="flex-1"
                            />

                            <Button
                                type="button"
                                variant="outline"
                                className="shrink-0 py-5"
                                onClick={onClickAddressSearch}
                            >
                                <MapPin className="h-4 w-4" />
                                검색
                            </Button>
                        </div>

                        {errors.address ? (
                            <p className="text-xs text-error-500">
                                {errors.address}
                            </p>
                        ) : null}

                        <Input
                            name="address_detail"
                            value={data.address_detail}
                            onChange={(e) =>
                                setData('address_detail', e.currentTarget.value)
                            }
                            placeholder="상세 주소 (예: 3층, 301호)"
                            autoComplete="address-line2"
                            error={!!errors.address_detail}
                        />

                        {errors.address_detail ? (
                            <p className="text-xs text-error-500">
                                {errors.address_detail}
                            </p>
                        ) : null}
                    </div>

                    {/* 소개/텍스트(현재는 Input으로 처리 — 나중에 Textarea 컴포넌트로 빼도 됨) */}
                    <div className="space-y-2">
                        <Label className="block">
                            <span className="text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                병원 소개
                            </span>
                        </Label>

                        <Input
                            name="description"
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.currentTarget.value)
                            }
                            placeholder="간단 소개를 입력하세요."
                            error={!!errors.description}
                        />

                        {errors.description ? (
                            <p className="text-xs text-error-500">
                                {errors.description}
                            </p>
                        ) : null}
                    </div>

                    <div className="space-y-2">
                        <Label className="block">
                            <span className="text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                운영 시간
                            </span>
                        </Label>

                        <Input
                            name="consulting_hours"
                            value={data.consulting_hours}
                            onChange={(e) =>
                                setData(
                                    'consulting_hours',
                                    e.currentTarget.value,
                                )
                            }
                            placeholder="예) 평일 10:00~19:00 / 토 10:00~15:00"
                            error={!!errors.consulting_hours}
                        />

                        {errors.consulting_hours ? (
                            <p className="text-xs text-error-500">
                                {errors.consulting_hours}
                            </p>
                        ) : null}
                    </div>

                    <div className="space-y-2">
                        <Label className="block">
                            <span className="text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                오시는 길
                            </span>
                        </Label>

                        <Input
                            name="direction"
                            value={data.direction}
                            onChange={(e) =>
                                setData('direction', e.currentTarget.value)
                            }
                            placeholder="예) 2호선 홍대입구역 3번 출구 도보 5분"
                            error={!!errors.direction}
                        />

                        {errors.direction ? (
                            <p className="text-xs text-error-500">
                                {errors.direction}
                            </p>
                        ) : null}
                    </div>

                    {/* 옵션 */}
                    <div className="pt-1">
                        <Label className="flex items-center gap-3">
                            <Checkbox
                            /*checked={data.activate_now}
                                onCheckedChange={(v) =>
                                    setData('activate_now', Boolean(v))
                                }*/
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
    );
}

CreateHospital.layout = (page: ReactNode) => (
    <AppLayout breadcrumbs={breadcrumbs}>{page}</AppLayout>
);

export default CreateHospital;
