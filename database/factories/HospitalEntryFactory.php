<?php

namespace Database\Factories;

use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\HospitalEntry\Models\HospitalEntry;
use Database\Factories\Support\SeedMediaFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HospitalEntry>
 */
final class HospitalEntryFactory extends Factory
{
    protected $model = HospitalEntry::class;

    public function definition(): array
    {
        $hospitalName = $this->hospitalName();
        $hasLicense = $this->faker->boolean(70);

        return [
            'hospital_name' => $hospitalName,
            'hospital_phone' => $this->faker->randomElement(['02', '031', '032', '051', '053']).'-'.$this->faker->numerify('####').'-'.$this->faker->numerify('####'),
            'address' => $this->faker->randomElement($this->addresses()),
            'address_detail' => $this->faker->randomElement(['2층', '3층', '4층', '5층', '6층 전체', '본관 2층', null]),
            'business_number' => $this->faker->unique()->numerify('##########'),
            'ceo_name' => $this->faker->randomElement($this->personNames()),
            'license_number' => $hasLicense ? $this->faker->unique()->numerify('#######') : null,
            'applicant_name' => $this->faker->randomElement($this->personNames()),
            'applicant_position' => $this->faker->randomElement(['대표원장', '원무팀장', '행정팀장', '마케팅팀장', '실장', '운영담당자']),
            'applicant_phone' => $this->faker->numerify('010-####-####'),
            'applicant_email' => $this->faker->unique()->safeEmail(),
            'allow_status' => $this->faker->randomElement(HospitalEntry::allowStatuses()),
            'created_at' => $this->faker->dateTimeBetween('-45 days', 'now'),
            'updated_at' => now(),
        ];
    }

    public function pending(): self
    {
        return $this->state(fn (): array => [
            'allow_status' => HospitalEntry::ALLOW_PENDING,
        ]);
    }

    public function approved(): self
    {
        return $this->state(fn (): array => [
            'allow_status' => HospitalEntry::ALLOW_APPROVED,
        ]);
    }

    public function reviewing(): self
    {
        return $this->state(fn (): array => [
            'allow_status' => HospitalEntry::ALLOW_REVIEWING,
        ]);
    }

    public function rejected(): self
    {
        return $this->state(fn (): array => [
            'allow_status' => HospitalEntry::ALLOW_REJECTED,
        ]);
    }

    public function withSeedMedia(): self
    {
        return $this->afterCreating(function (HospitalEntry $entry): void {
            $mediaAttachAction = app(MediaAttachDeleteAction::class);

            $mediaAttachAction->attachOne(
                $entry,
                SeedMediaFactory::image("hospital-entry-business-registration-{$entry->id}"),
                'hospital_entry_business_registration_file',
                'hospital-entry',
                'business-registration-file',
                true,
            );

            if ($entry->license_number === null) {
                return;
            }

            $mediaAttachAction->attachOne(
                $entry,
                SeedMediaFactory::image("hospital-entry-license-{$entry->id}"),
                'hospital_entry_license_file',
                'hospital-entry',
                'license-file',
                true,
            );
        });
    }

    /**
     * @return array<int, string>
     */
    private function hospitalName(): string
    {
        static $sequence = 0;

        $names = $this->hospitalNames();
        $name = $names[$sequence % count($names)];
        $round = intdiv($sequence, count($names));
        $sequence++;

        return $round === 0 ? $name : $name.' '.$round;
    }

    /**
     * @return array<int, string>
     */
    private function hospitalNames(): array
    {
        return [
            '서울뷰성형외과의원',
            '강남라인피부과의원',
            '더웰성형외과의원',
            '청담하나의원',
            '리앤유피부과의원',
            '미소라인의원',
            '압구정바른성형외과의원',
            '센트럴뷰의원',
            '디에이치피부과의원',
            '연세라온의원',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function addresses(): array
    {
        return [
            '서울특별시 강남구 테헤란로 152',
            '서울특별시 강남구 도산대로 318',
            '서울특별시 서초구 강남대로 465',
            '서울특별시 강남구 압구정로 165',
            '서울특별시 송파구 올림픽로 300',
            '경기도 성남시 분당구 황새울로 258번길 10',
            '경기도 수원시 팔달구 권광로 181',
            '부산광역시 부산진구 중앙대로 672',
            '대구광역시 중구 달구벌대로 2095',
            '인천광역시 남동구 예술로 138',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function personNames(): array
    {
        return [
            '김민준',
            '이서연',
            '박지훈',
            '최유진',
            '정도윤',
            '한지우',
            '오세현',
            '윤하린',
            '강민서',
            '신재원',
        ];
    }
}
