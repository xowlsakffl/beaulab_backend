<?php

namespace Database\Seeders;

use App\Common\Authorization\AccessRoles;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Models\Chat;
use App\Domains\Chat\Support\ChatMatchKey;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalWallet\Models\HospitalWallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

/** One-time, synthetic fixtures for a new frontend developer's empty local database. */
final class FrontendDevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        $password = (string) config('seeding.frontend.password');
        if (! app()->environment('local') || ! config('seeding.frontend.enabled')) {
            throw new LogicException('Requires APP_ENV=local and FRONTEND_SEED_ENABLED=true.');
        }
        if (strlen($password) < 12) {
            throw new LogicException('Set a private FRONTEND_SEED_PASSWORD of at least 12 characters.');
        }
        if (config('sms.enabled') || config('push.enabled')) {
            throw new LogicException('Disable SMS_ENABLED and PUSH_ENABLED before creating fixtures.');
        }

        $connection = DB::connection();
        if ($connection->getDriverName() !== 'mysql'
            || ! in_array($connection->getConfig('host'), ['127.0.0.1', 'localhost', '::1'], true)) {
            throw new LogicException('Only a local MySQL connection is supported.');
        }
        $database = (string) $connection->selectOne('SELECT DATABASE() AS name')->name;
        if (! preg_match('/^beaulab_frontend_[a-z0-9_]+$/', $database)) {
            throw new LogicException('Use a dedicated database named beaulab_frontend_<name>.');
        }
        $lock = 'frontend-seed:'.substr(hash('sha256', $database), 0, 40);
        if ((int) $connection->selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$lock])->acquired !== 1) {
            throw new LogicException('Another frontend seed is running.');
        }

        try {
            // Reject existing data before AuthorizationSeeder can change roles or permissions.
            foreach ($connection->select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'") as $table) {
                $name = (string) array_values((array) $table)[0];
                if (! in_array($name, ['migrations', 'hospital_event_ad_booking_locks', 'hospital_promotion_slot_locks'], true) && $connection->table($name)->exists()) {
                    throw new LogicException("Database is not empty ({$name}). Existing data was not modified.");
                }
            }
            // Fixtures must not trigger observers, notifications or outbound delivery.
            $result = $connection->transaction(function () use ($password): array {
                $this->call([AuthorizationSeeder::class, CategorySeeder::class, HospitalFeatureSeeder::class]);

                return Model::withoutEvents(fn () => $this->createFixtures($password));
            });
        } finally {
            $connection->selectOne('SELECT RELEASE_LOCK(?)', [$lock]);
        }

        $this->command?->info('Local fixtures created. Passwords were not printed. Do not rerun on this database.');
        $this->command?->table(['Kind', 'ID', 'Login / name'], $result);
    }

    private function createFixtures(string $password): array
    {
        $rows = [];
        $staff = AccountStaff::factory()->withPassword($password)->asSuperAdmin()->create([
            'name' => '개발 관리자', 'nickname' => 'frontend_admin', 'email' => 'admin@frontend.test',
        ]);
        $rows[] = ['staff', $staff->id, $staff->nickname];
        $restricted = AccountStaff::factory()->withPassword($password)->create([
            'name' => '권한 없는 직원', 'nickname' => 'frontend_restricted', 'email' => 'restricted@frontend.test',
        ]);
        $rows[] = ['staff (no permissions)', $restricted->id, $restricted->nickname];

        $users = [];
        foreach (['alice', 'bob', 'empty', 'suspended'] as $name) {
            $users[$name] = AccountUser::factory()->withPassword($password)->create([
                'name' => '개발 사용자 '.$name, 'nickname' => 'frontend_'.$name,
                'email' => $name.'@frontend.test', 'phone' => null,
                'status' => $name === 'suspended' ? AccountUser::STATUS_SUSPENDED : AccountUser::STATUS_ACTIVE,
                'last_accessed_at' => null, 'last_access_ip' => null,
            ]);
            $rows[] = ['user', $users[$name]->id, $users[$name]->email];
        }

        foreach (['alpha', 'beta'] as $name) {
            $hospital = Hospital::factory()->approved()->active()->withSeedMedia(1)->create([
                'name' => '개발 병원 '.$name, 'address' => '개발용 가상 주소',
                'description' => '실제 병원이 아닌 로컬 테스트 데이터입니다.',
                'youtube_link' => null, 'tel' => null, 'ad_reception_phone_1' => null,
                'ad_reception_phone_2' => null, 'ad_reception_phone_3' => null,
            ]);
            $rows[] = ['hospital', $hospital->id, $hospital->name];
            $owner = AccountHospital::factory()->forHospital($hospital)->withPassword($password)->create([
                'email' => 'hospital-'.$name.'@frontend.test', 'nickname' => 'frontend_hospital_'.$name,
            ]);
            $owner->syncRoles([AccessRoles::HOSPITAL_OWNER]);
            $rows[] = ['hospital account', $owner->id, $owner->nickname];
            // A zero wallet is consistent with an empty ledger; do not invent paid balance.
            HospitalWallet::query()->create([
                'hospital_id' => $hospital->id, 'paid_balance' => 0,
                'reserved_paid_balance' => 0, 'service_balance' => 0,
            ]);
            foreach (['open', 'closed'] as $state) {
                $event = HospitalEvent::factory()->forHospital($hospital)->textType()->approved()->active()->withSeedMedia()->create([
                    'name' => '개발 이벤트 '.$name.' '.$state,
                    'manager_staff_id' => $staff->id,
                    'is_event_period_unlimited' => $state === 'open',
                    'event_start_at' => now()->subDays(30),
                    'event_end_at' => $state === 'open' ? null : now()->subDay(),
                ]);
                $rows[] = ['event', $event->id, $event->name];
            }
        }

        $chat = Chat::query()->create([
            'match_key' => ChatMatchKey::forUsers($users['alice']->id, $users['bob']->id),
            'created_by_user_id' => $users['alice']->id,
        ]);
        foreach (['alice', 'bob'] as $name) {
            $chat->participants()->create(['account_user_id' => $users[$name]->id]);
        }
        foreach (['alice', 'bob'] as $name) {
            $message = $chat->messages()->create([
                'sender_user_id' => $users[$name]->id, 'client_message_id' => 'frontend-fixture-'.$name,
                'body' => '개발용 대화입니다: '.$name, 'broadcast_pending' => false,
            ]);
        }
        $chat->update(['last_message_id' => $message->id, 'last_message_at' => $message->created_at]);
        $rows[] = ['chat (alice / bob)', $chat->id, '2 messages, no outbound notification'];

        return $rows;
    }
}
