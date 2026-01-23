import AppLayout from '@/layouts/admin/app-layout';
import { useState } from 'react';
import type { ReactNode } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

const OTP_MAX_LENGTH = 6;

function UiPreviewPage() {
    const [checked, setChecked] = useState(false);
    const [otp, setOtp] = useState('');
    const [showError] = useState(true);

    return (
        <>
            <div className="space-y-10">
                <div>
                    <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">
                        UI Preview (legacy components)
                    </h1>
                    <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        기존 <code className="font-mono">components/ui</code>{' '}
                        컴포넌트 미리보기 페이지
                    </p>
                </div>

                <section className="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                        Buttons
                    </h2>

                    <div className="mt-4 flex flex-wrap gap-3">
                        <Button type="button">Primary</Button>
                        <Button type="button" variant="secondary">
                            Secondary
                        </Button>
                        <Button type="button" variant="outline">
                            Outline
                        </Button>
                        <Button type="button" disabled>
                            Disabled
                        </Button>
                        <Button type="button">
                            <Spinner />
                            With Spinner
                        </Button>
                    </div>
                </section>

                <section className="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                        Inputs / Errors
                    </h2>

                    <div className="mt-4 grid gap-6 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="example-text">Text</Label>
                            <Input
                                id="example-text"
                                placeholder="Type something..."
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="example-error">Error</Label>
                            <Input
                                id="example-error"
                                placeholder="This one shows error"
                                className="border-red-500 focus-visible:ring-red-500"
                            />
                            {showError && (
                                <InputError message="에러 메시지 샘플입니다." />
                            )}
                        </div>
                    </div>
                </section>

                <section className="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                        Checkbox
                    </h2>

                    <div className="mt-4 flex items-center gap-3">
                        <Checkbox
                            id="preview-checkbox"
                            checked={checked}
                            onCheckedChange={(v) => setChecked(Boolean(v))}
                        />
                        <Label htmlFor="preview-checkbox">Remember me</Label>
                    </div>
                </section>

                <section className="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                        OTP
                    </h2>

                    <div className="mt-4 flex flex-col gap-3">
                        <InputOTP
                            value={otp}
                            onChange={setOtp}
                            maxLength={OTP_MAX_LENGTH}
                        >
                            <InputOTPGroup>
                                {Array.from(
                                    { length: OTP_MAX_LENGTH },
                                    (_, i) => (
                                        <InputOTPSlot key={i} index={i} />
                                    ),
                                )}
                            </InputOTPGroup>
                        </InputOTP>

                        <div className="text-sm text-gray-500 dark:text-gray-400">
                            Value: <code className="font-mono">{otp}</code>
                        </div>
                    </div>
                </section>
            </div>
        </>
    );
}

UiPreviewPage.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;

export default UiPreviewPage;
