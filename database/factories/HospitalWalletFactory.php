<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalWallet\Models\HospitalWallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HospitalWallet>
 */
final class HospitalWalletFactory extends Factory
{
    protected $model = HospitalWallet::class;

    public function definition(): array
    {
        return [
            'hospital_id' => Hospital::factory(),
            'paid_balance' => 0,
            'service_balance' => 0,
            'last_transaction_at' => null,
        ];
    }

    public function forHospital(Hospital|int $hospital): self
    {
        return $this->state(fn (): array => [
            'hospital_id' => $hospital instanceof Hospital ? $hospital->getKey() : $hospital,
        ]);
    }
}
