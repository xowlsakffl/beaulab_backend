<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\HospitalWallet;

use App\Domains\HospitalWallet\Models\HospitalWalletTransaction;
use App\Domains\HospitalWallet\Models\HospitalWalletTransactionEntry;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HospitalWalletLedgerImmutabilityTest extends TestCase
{
    private Dispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = new Dispatcher(new Container);
        Model::setEventDispatcher($this->dispatcher);
        Model::clearBootedModels();
    }

    protected function tearDown(): void
    {
        Model::unsetEventDispatcher();
        parent::tearDown();
    }

    #[DataProvider('ledgerModels')]
    public function test_ledger_models_cannot_be_updated(string $modelClass): void
    {
        $model = new $modelClass;
        $this->expectException(LogicException::class);
        $this->dispatcher->until("eloquent.updating: {$modelClass}", [$model]);
    }

    #[DataProvider('ledgerModels')]
    public function test_ledger_models_cannot_be_deleted(string $modelClass): void
    {
        $model = new $modelClass;
        $this->expectException(LogicException::class);
        $this->dispatcher->until("eloquent.deleting: {$modelClass}", [$model]);
    }

    public static function ledgerModels(): array
    {
        return [
            HospitalWalletTransaction::class => [HospitalWalletTransaction::class],
            HospitalWalletTransactionEntry::class => [HospitalWalletTransactionEntry::class],
        ];
    }
}
