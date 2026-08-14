<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Support;

use InvalidArgumentException;

final class HospitalWalletSms
{
    public const string PURPOSE_BALANCE_NOTICE = 'HOSPITAL_WALLET_BALANCE_NOTICE';

    public const string RECIPIENT_MANAGER = 'MANAGER';

    public const string RECIPIENT_REPRESENTATIVE = 'REPRESENTATIVE';

    public const string MESSAGE_PART_TEXT = 'TEXT';

    public const string MESSAGE_PART_VARIABLE = 'VARIABLE';

    public const string VARIABLE_HOSPITAL_NAME = 'HOSPITAL_NAME';

    public const string VARIABLE_REMAINING_BALANCE = 'REMAINING_BALANCE';

    public const array MESSAGE_PART_TYPES = [
        self::MESSAGE_PART_TEXT,
        self::MESSAGE_PART_VARIABLE,
    ];

    public const array VARIABLE_KEYS = [
        self::VARIABLE_HOSPITAL_NAME,
        self::VARIABLE_REMAINING_BALANCE,
    ];

    public static function recipientKindLabel(string $kind): string
    {
        return match ($kind) {
            self::RECIPIENT_MANAGER => '담당자',
            self::RECIPIENT_REPRESENTATIVE => '대표자',
            default => $kind,
        };
    }

    /**
     * @param  array<int, mixed>  $parts
     * @return list<array{type: string, text?: string, key?: string}>
     */
    public static function normalizeMessageParts(array $parts): array
    {
        $normalized = [];

        foreach ($parts as $part) {
            if (! is_array($part)) {
                continue;
            }

            $type = (string) ($part['type'] ?? '');
            if ($type === self::MESSAGE_PART_TEXT) {
                $text = (string) ($part['text'] ?? '');
                if ($text === '') {
                    continue;
                }

                $lastIndex = array_key_last($normalized);
                if ($lastIndex !== null && $normalized[$lastIndex]['type'] === self::MESSAGE_PART_TEXT) {
                    $normalized[$lastIndex]['text'] .= $text;
                } else {
                    $normalized[] = ['type' => self::MESSAGE_PART_TEXT, 'text' => $text];
                }

                continue;
            }

            if ($type === self::MESSAGE_PART_VARIABLE) {
                $normalized[] = [
                    'type' => self::MESSAGE_PART_VARIABLE,
                    'key' => (string) ($part['key'] ?? ''),
                ];
            }
        }

        return $normalized;
    }

    /**
     * @param  list<array{type: string, text?: string, key?: string}>  $parts
     * @param  array<string, string>  $variables
     */
    public static function renderMessage(array $parts, array $variables): string
    {
        return collect($parts)
            ->map(static function (array $part) use ($variables): string {
                return match ($part['type']) {
                    self::MESSAGE_PART_TEXT => (string) ($part['text'] ?? ''),
                    self::MESSAGE_PART_VARIABLE => self::variableValue(
                        (string) ($part['key'] ?? ''),
                        $variables,
                    ),
                    default => throw new InvalidArgumentException('지원하지 않는 문자 템플릿 조각입니다.'),
                };
            })
            ->implode('');
    }

    /**
     * @param  list<array{type: string, text?: string, key?: string}>  $parts
     */
    public static function displayTemplate(array $parts): string
    {
        return self::renderMessage($parts, [
            self::VARIABLE_HOSPITAL_NAME => '[병의원명]',
            self::VARIABLE_REMAINING_BALANCE => '[잔여충전금]',
        ]);
    }

    /**
     * @param  array<string, string>  $variables
     */
    private static function variableValue(string $key, array $variables): string
    {
        if (! in_array($key, self::VARIABLE_KEYS, true)) {
            throw new InvalidArgumentException("지원하지 않는 문자 템플릿 변수입니다: {$key}");
        }

        return $variables[$key] ?? '';
    }
}
