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
            throw new LogicException('APP_ENV=local 및 FRONTEND_SEED_ENABLED=true 설정이 필요합니다.');
        }
        if (strlen($password) < 12) {
            throw new LogicException('FRONTEND_SEED_PASSWORD에 외부에 공유하지 않는 12자 이상의 비밀번호를 설정해 주세요.');
        }
        if (config('sms.enabled') || config('push.enabled')) {
            throw new LogicException('시드 생성 전에 SMS_ENABLED와 PUSH_ENABLED를 비활성화해 주세요.');
        }

        $connection = DB::connection();
        if ($connection->getDriverName() !== 'mysql'
            || ! in_array($connection->getConfig('host'), ['127.0.0.1', 'localhost', '::1'], true)) {
            throw new LogicException('로컬 MySQL 연결만 지원합니다.');
        }
        $database = (string) $connection->selectOne('SELECT DATABASE() AS name')->name;
        if (! preg_match('/^beaulab_frontend_[a-z0-9_]+$/', $database)) {
            throw new LogicException('beaulab_frontend_<name> 형식의 전용 데이터베이스를 사용해 주세요.');
        }
        $lock = 'frontend-seed:'.substr(hash('sha256', $database), 0, 40);
        if ((int) $connection->selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$lock])->acquired !== 1) {
            throw new LogicException('다른 프론트 개발 시드 생성 작업이 실행 중입니다.');
        }

        try {
            // Reject existing data before AuthorizationSeeder can change roles or permissions.
            foreach ($connection->select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'") as $table) {
                $name = (string) array_values((array) $table)[0];
                if (! in_array($name, ['migrations', 'hospital_event_ad_booking_locks', 'hospital_promotion_slot_locks'], true) && $connection->table($name)->exists()) {
                    throw new LogicException("데이터베이스가 비어 있지 않습니다({$name}). 기존 데이터는 변경하지 않았습니다.");
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

        $this->command?->info('로컬 시드 생성을 완료했습니다. 비밀번호는 출력하지 않았습니다. 이 데이터베이스에서 다시 실행하지 마세요.');
        $this->command?->table(['구분', 'ID', '로그인 정보 / 이름'], $result);
    }

    private function createFixtures(string $password): array
    {
        $rows = [];
        $staff = AccountStaff::factory()->withPassword($password)->asSuperAdmin()->create([
            'name' => '개발 관리자', 'nickname' => 'frontend_admin', 'email' => 'admin@frontend.test',
        ]);
        $rows[] = ['직원', $staff->id, $staff->nickname];
        $restricted = AccountStaff::factory()->withPassword($password)->create([
            'name' => '권한 없는 직원', 'nickname' => 'frontend_restricted', 'email' => 'restricted@frontend.test',
        ]);
        $rows[] = ['직원 (권한 없음)', $restricted->id, $restricted->nickname];

        $users = [];
        foreach (['alice' => '가', 'bob' => '나', 'empty' => '빈 내역', 'suspended' => '이용 정지'] as $name => $label) {
            $users[$name] = AccountUser::factory()->withPassword($password)->create([
                'name' => '개발 사용자 '.$label, 'nickname' => 'frontend_'.$name,
                'email' => $name.'@frontend.test', 'phone' => null,
                'status' => $name === 'suspended' ? AccountUser::STATUS_SUSPENDED : AccountUser::STATUS_ACTIVE,
                'last_accessed_at' => null, 'last_access_ip' => null,
            ]);
            $rows[] = ['회원', $users[$name]->id, $users[$name]->email];
        }

        foreach (['alpha' => '가', 'beta' => '나'] as $name => $label) {
            $hospital = Hospital::factory()->approved()->active()->withSeedMedia(1)->create([
                'name' => '개발 병원 '.$label, 'address' => '개발용 가상 주소',
                'description' => '실제 병원이 아닌 로컬 테스트 데이터입니다.',
                'youtube_link' => null, 'tel' => null, 'ad_reception_phone_1' => null,
                'ad_reception_phone_2' => null, 'ad_reception_phone_3' => null,
            ]);
            $rows[] = ['병의원', $hospital->id, $hospital->name];
            $owner = AccountHospital::factory()->forHospital($hospital)->withPassword($password)->create([
                'email' => 'hospital-'.$name.'@frontend.test', 'nickname' => 'frontend_hospital_'.$name,
            ]);
            $owner->syncRoles([AccessRoles::HOSPITAL_OWNER]);
            $rows[] = ['병의원 계정', $owner->id, $owner->nickname];
            // A zero wallet is consistent with an empty ledger; do not invent paid balance.
            HospitalWallet::query()->create([
                'hospital_id' => $hospital->id, 'paid_balance' => 0,
                'reserved_paid_balance' => 0, 'service_balance' => 0,
            ]);
            foreach (['open', 'closed'] as $state) {
                $event = HospitalEvent::factory()->forHospital($hospital)->textType()->approved()->active()->withSeedMedia()->create([
                    'name' => '개발 이벤트 '.$label.' '.($state === 'open' ? '진행중' : '종료'),
                    'manager_staff_id' => $staff->id,
                    'is_event_period_unlimited' => $state === 'open',
                    'event_start_at' => now()->subDays(30),
                    'event_end_at' => $state === 'open' ? null : now()->subDay(),
                ]);
                $rows[] = ['이벤트', $event->id, $event->name];
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
                'body' => '개발용 대화입니다: '.$users[$name]->name, 'broadcast_pending' => false,
            ]);
        }
        $chat->update(['last_message_id' => $message->id, 'last_message_at' => $message->created_at]);
        $rows[] = ['채팅 (개발 사용자 가 / 나)', $chat->id, '메시지 2건, 외부 알림 미발송'];

        return $rows;
    }
}
