<?php

namespace Tests\Unit;

use App\Modules\Staff\Http\Requests\RemoteConsultation\RemoteConsultationAssignForStaffRequest;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RemoteConsultationAssignmentValidationTest extends TestCase
{
    #[DataProvider('payloads')]
    public function test_assignment_payload_validation(array $payload, bool $expected): void
    {
        $factory = new Factory(new Translator(new ArrayLoader, 'en'));
        $validator = $factory->make($payload, (new RemoteConsultationAssignForStaffRequest)->rules());
        self::assertSame($expected, $validator->passes());
    }

    public static function payloads(): array
    {
        return [
            'multiple' => [['hospital_ids' => [1, 2]], true],
            'maximum' => [['hospital_ids' => [1, 2, 3, 4, 5, 6]], true],
            'legacy single' => [['hospital_id' => 1], true],
            'both' => [['hospital_id' => 1, 'hospital_ids' => [2]], false],
            'neither' => [[], false],
            'empty' => [['hospital_ids' => []], false],
            'duplicate' => [['hospital_ids' => [1, 1]], false],
            'over limit' => [['hospital_ids' => [1, 2, 3, 4, 5, 6, 7]], false],
            'invalid id' => [['hospital_ids' => [0]], false],
            'wrong type' => [['hospital_ids' => '1,2'], false],
        ];
    }
}
