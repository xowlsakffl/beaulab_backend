<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Hospital;

use App\Domains\HospitalChatBot\Queries\Hospital\HospitalChatBotAvailabilityForHospitalQuery;
use App\Domains\HospitalChatBot\Support\HospitalChatBotAvailabilityResolver;
use App\Domains\HospitalChatBot\Support\HospitalChatBotLog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Throwable;

final class HospitalChatBotAvailabilityForHospitalAction
{
    public function __construct(
        private readonly HospitalChatBotAvailabilityForHospitalQuery $query,
        private readonly HospitalChatBotAvailabilityResolver $resolver,
    ) {}

    public function execute(): array
    {
        try {
            $now = now(HospitalChatBotAvailabilityResolver::TIMEZONE);
            $hours = $this->query->hours();
            $closures = $this->query->closures();
            $setting = $this->query->setting();

            return [...$this->resolver->resolve(
                $now, $hours, $closures, $setting,
            ),
                'operating_schedule' => $this->resolver->schedule($now, $hours, $closures, $setting),
                'welcome_message' => app(\App\Domains\HospitalChatBot\Queries\HospitalChatBotMessageSettingQuery::class)->current()->welcome_message,
            ];
        } catch (Throwable $exception) {
            /*
             * 운영시간 계산이 깨졌다(PRD 16.6).
             *
             * 여기서 예외를 그대로 올리면 SDK가 Q&A조차 못 연다. 운영시간을 모를 때의
             * 안전한 답은 "지금은 연결할 수 없다"이지 500이 아니다. 잘못 닫으면 상담이
             * 한 건 늦어지지만, 잘못 열면 아무도 받지 않는 요청이 쌓인다.
             */
            Log::error('챗봇 운영시간 계산 실패', [
                'event' => HospitalChatBotLog::OPERATING_HOURS_FAILED,
                'exception' => $exception::class,
                'exception_code' => $exception->getCode(),
            ]);

            return [
                'timezone' => HospitalChatBotAvailabilityResolver::TIMEZONE,
                'checked_at' => CarbonImmutable::now(HospitalChatBotAvailabilityResolver::TIMEZONE)->toIso8601String(),
                'is_available' => false,
                'reason' => HospitalChatBotAvailabilityResolver::REASON_CONFIGURATION_UNAVAILABLE,
                'message' => '상담 가능 시간을 확인하지 못했습니다. 잠시 후 다시 시도해 주세요.',
                'next_open_at' => null,
            ];
        }
    }
}
