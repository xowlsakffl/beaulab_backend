<?php

declare(strict_types=1);

namespace Tests\Unit\Common;

use App\Providers\TelescopeServiceProvider;
use Illuminate\Foundation\Testing\TestCase;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\Watchers\RequestWatcher;

final class TelescopePrivacyTest extends TestCase
{
    public function test_sensitive_requests_and_queries_are_excluded_and_account_parameters_are_masked(): void
    {
        $filters = Telescope::$filterUsing;
        $requestParameters = Telescope::$hiddenRequestParameters;
        $responseParameters = Telescope::$hiddenResponseParameters;
        $headers = Telescope::$hiddenRequestHeaders;

        try {
            (new TelescopeServiceProvider($this->app))->register();
            $filter = end(Telescope::$filterUsing);
            foreach ([
                '/api/v1/staff/hospital-chatbot/consultations/1/messages',
                '/api/v1/hospital/hospital-chatbot/consultations/1/messages?after_sequence=1',
                '/api/v1/user/chats',
                '/api/v1/user/chats/messages',
                '/api/v1/user/chats/1/messages',
            ] as $uri) {
                self::assertFalse($filter(IncomingEntry::make(['uri' => $uri, 'response_status' => 500])->type(EntryType::REQUEST)));
            }
            self::assertTrue($filter(IncomingEntry::make([
                'uri' => '/api/v1/staff/hospitals', 'response_status' => 500,
            ])->type(EntryType::REQUEST)));
            foreach (['hospital_chatbot_consultation_messages', 'chat_messages', 'settlement_account_number'] as $columnOrTable) {
                self::assertFalse($filter(IncomingEntry::make(['sql' => 'select '.$columnOrTable.' from hospitals'])->type(EntryType::QUERY)));
            }

            $watcher = new class extends RequestWatcher
            {
                public function mask(array $data, array $hidden): array
                {
                    return $this->hideParameters($data, $hidden);
                }
            };
            $request = $watcher->mask([
                'password' => 'secret', 'settlement_account_number' => '1234567890', 'account_number' => '9876543210',
            ], Telescope::$hiddenRequestParameters);
            self::assertSame(['********', '********', '********'], array_values($request));
            $response = $watcher->mask(['data' => [
                'business_registration' => ['settlement_account' => ['account_number' => '1234567890']],
                'refund' => ['account_number' => '9876543210'],
            ]], Telescope::$hiddenResponseParameters);
            self::assertSame('********', $response['data']['business_registration']['settlement_account']['account_number']);
            self::assertSame('********', $response['data']['refund']['account_number']);
        } finally {
            Telescope::$filterUsing = $filters;
            Telescope::$hiddenRequestParameters = $requestParameters;
            Telescope::$hiddenResponseParameters = $responseParameters;
            Telescope::$hiddenRequestHeaders = $headers;
        }
    }
}
