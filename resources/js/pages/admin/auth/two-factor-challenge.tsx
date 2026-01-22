import InputError from '@/components/input-error';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { OTP_MAX_LENGTH } from '@/hooks/use-two-factor-auth';
import AuthLayout from '@/layouts/admin/auth-layout';
import { store } from '@/routes/two-factor/login';
import { Form, Head } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { useMemo, useState } from 'react';

export default function TwoFactorChallenge() {
    const [showRecoveryInput, setShowRecoveryInput] = useState<boolean>(false);
    const [code, setCode] = useState<string>('');

    const authConfigContent = useMemo(() => {
        if (showRecoveryInput) {
            return {
                title: '복구 코드 입력',
                description: '복구 코드 중 하나를 입력해서 로그인하세요.',
                toggleText: '인증 코드로 로그인하기',
            };
        }

        return {
            title: '인증 코드 입력',
            description: '인증 앱에 표시된 코드를 입력하세요.',
            toggleText: '복구 코드로 로그인하기',
        };
    }, [showRecoveryInput]);

    return (
        <AuthLayout>
            <Head title="Two-Factor Authentication" />

            <div className="mb-5 sm:mb-8">
                <h1 className="mb-2 text-title-sm font-semibold text-gray-800 sm:text-title-md dark:text-white/90">
                    {authConfigContent.title}
                </h1>
                <p className="text-sm text-gray-500 dark:text-gray-400">
                    {authConfigContent.description}
                </p>
            </div>

            <Form
                {...store.form()}
                className="space-y-6"
                resetOnError
                resetOnSuccess={!showRecoveryInput}
            >
                {({ errors, processing, clearErrors }) => (
                    <>
                        {showRecoveryInput ? (
                            <div>
                                <label className="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    복구 코드{' '}
                                    <span className="text-error-500">*</span>
                                </label>

                                <input
                                    name="recovery_code"
                                    type="text"
                                    placeholder="복구 코드를 입력하세요."
                                    autoFocus
                                    required
                                    className={[
                                        'h-11 w-full rounded-lg border bg-white px-4 text-sm text-gray-900 transition outline-none',
                                        'border-gray-200 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10',
                                        'dark:border-gray-800 dark:bg-gray-950 dark:text-white/90 dark:focus:ring-brand-500/20',
                                        errors.recovery_code
                                            ? 'border-error-500 focus:border-error-500 focus:ring-error-500/10 dark:focus:ring-error-500/20'
                                            : '',
                                    ].join(' ')}
                                />

                                <InputError
                                    message={errors.recovery_code}
                                    className="mt-2"
                                />
                            </div>
                        ) : (
                            <div className="flex flex-col items-center justify-center space-y-3 text-center">
                                <div className="flex w-full items-center justify-center">
                                    <InputOTP
                                        name="code"
                                        maxLength={OTP_MAX_LENGTH}
                                        value={code}
                                        onChange={(value) => setCode(value)}
                                        disabled={processing}
                                        pattern={REGEXP_ONLY_DIGITS}
                                    >
                                        <InputOTPGroup>
                                            {Array.from(
                                                { length: OTP_MAX_LENGTH },
                                                (_, index) => (
                                                    <InputOTPSlot
                                                        key={index}
                                                        index={index}
                                                    />
                                                ),
                                            )}
                                        </InputOTPGroup>
                                    </InputOTP>
                                </div>
                                <InputError message={errors.code} />
                            </div>
                        )}

                        <button
                            type="submit"
                            className="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand-500 px-5 py-3 text-sm font-medium text-white transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-70"
                            disabled={processing}
                        >
                            계속
                        </button>

                        <div className="text-center text-sm text-gray-500 dark:text-gray-400">
                            <span>또는 </span>
                            <button
                                type="button"
                                className="text-brand-500 hover:text-brand-600 dark:text-brand-400"
                                onClick={() => {
                                    setShowRecoveryInput(!showRecoveryInput);
                                    clearErrors();
                                    setCode('');
                                }}
                            >
                                {authConfigContent.toggleText}
                            </button>
                        </div>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
