<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\HospitalChatBot;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQnaFolder;
use Database\Seeders\HospitalChatBotQnaSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Spatie\Permission\Models\Permission;

final class HospitalChatBotQnaFlowTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication(): Application
    {
        $app = parent::createApplication();

        if ($app->configurationIsCached() || config('database.connections.mysql.database') !== 'beaulab_testing') {
            throw new \LogicException('Feature tests require uncached configuration and the beaulab_testing database.');
        }

        return $app;
    }

    public function test_staff_manages_qnas_and_hospital_only_receives_active_plain_text_fields(): void
    {
        $staff = AccountStaff::factory()->create();
        $staff->givePermissionTo(Permission::findOrCreate(AccessPermissions::COMMON_ACCESS, 'staff'));
        $this->login('staff', $staff, 'http://localhost:3000');

        $created = $this->postJson('/api/v1/staff/hospital-chatbot/qnas', [
            'folder_id' => $this->folder('병원회원')->id,
            'question' => '계정은 어떻게 변경하나요?',
            'answer' => '<strong>계정 메뉴</strong>에서 변경해 주세요.',
            'status' => HospitalChatBotQna::STATUS_ACTIVE,
            'sort_order' => 2,
        ])->assertCreated();

        $qnaId = (int) $created->json('data.id');

        $this->postJson('/api/v1/staff/hospital-chatbot/qnas', [
            'folder_id' => $this->folder('병원회원')->id,
            'question' => '  계정은   어떻게 변경하나요? ',
            'answer' => '중복 답변',
        ])->assertUnprocessable()
            ->assertJsonPath('error.details.errors.question.0', '동일한 질문이 이미 등록되어 있습니다.');

        $this->getJson('/api/v1/staff/hospital-chatbot/qnas?q=%EA%B3%84%EC%A0%95')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->patchJson("/api/v1/staff/hospital-chatbot/qnas/{$qnaId}", ['sort_order' => 1])
            ->assertOk()
            ->assertJsonPath('data.sort_order', 1);

        $this->app['auth']->forgetGuards();
        $hospital = Hospital::factory()->active()->create();
        $hospitalAccount = AccountHospital::factory()->forHospital($hospital)->active()->create();
        $this->login('hospital', $hospitalAccount, 'http://localhost:3002');

        HospitalChatBotQna::query()->create([
            'folder_id' => $this->folder('숨김')->id,
            'question' => '비공개 질문',
            'question_key' => HospitalChatBotQna::questionKey('비공개 질문'),
            'answer' => '비공개 답변',
            'status' => HospitalChatBotQna::STATUS_INACTIVE,
            'sort_order' => 0,
        ]);

        $this->getJson('/api/v1/hospital/hospital-chatbot/qnas')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $qnaId)
            ->assertJsonPath('data.0.answer', '<strong>계정 메뉴</strong>에서 변경해 주세요.')
            ->assertJsonMissingPath('data.0.status')
            ->assertJsonMissingPath('data.0.created_by_staff_id');
    }

    public function test_active_question_key_is_unique_and_seeder_does_not_restore_deleted_qna(): void
    {
        $question = '중복 방지 질문';
        $first = $this->createQna($question);

        try {
            $this->createQna($question);
            self::fail('활성 Q&A의 question_key 중복이 허용되었습니다.');
        } catch (UniqueConstraintViolationException) {
            self::assertSame(1, HospitalChatBotQna::query()->where('question_key', $first->question_key)->count());
        }

        $first->delete();
        self::assertNotNull($this->createQna($question)->id);

        $seedItem = json_decode(
            file_get_contents(database_path('seeders/data/hospital_chatbot_qnas.json')) ?: '[]',
            true,
            flags: JSON_THROW_ON_ERROR,
        )[0];
        $seedQuestion = (string) $seedItem['question'];
        $deletedSeedQna = $this->createQna($seedQuestion);
        $deletedSeedQna->delete();

        $this->seed(HospitalChatBotQnaSeeder::class);

        self::assertTrue(HospitalChatBotQna::onlyTrashed()->whereKey($deletedSeedQna->id)->exists());
        self::assertFalse(HospitalChatBotQna::query()
            ->where('question_key', HospitalChatBotQna::questionKey($seedQuestion))
            ->exists());
    }

    private function folder(string $name): HospitalChatBotQnaFolder
    {
        return HospitalChatBotQnaFolder::query()->firstOrCreate(
            ['parent_id' => null, 'name' => $name],
            ['depth' => 1, 'sort_order' => 0],
        );
    }

    private function createQna(string $question): HospitalChatBotQna
    {
        return HospitalChatBotQna::query()->create([
            'folder_id' => $this->folder('테스트')->id,
            'category' => '테스트',
            'question' => $question,
            'question_key' => HospitalChatBotQna::questionKey($question),
            'answer' => '테스트 답변',
            'status' => HospitalChatBotQna::STATUS_ACTIVE,
            'sort_order' => 1,
        ]);
    }

    private function login(string $actor, AccountStaff|AccountHospital $account, string $origin): void
    {
        $account->forceFill(['password' => 'password1234'])->save();
        $this->withCredentials()->withHeaders([
            'X-Beaulab-Client' => 'web',
            'Origin' => $origin,
        ]);
        $csrf = $this->getJson("/api/v1/{$actor}/auth/csrf")->assertOk();
        $this->withCookie("beaulab_{$actor}_session", $csrf->getCookie("beaulab_{$actor}_session")->getValue());
        $this->withHeader('X-CSRF-TOKEN', $csrf->json('data.csrf_token'));
        $this->app['auth']->forgetGuards();

        $login = $this->postJson("/api/v1/{$actor}/auth/login", [
            'nickname' => $account->nickname,
            'password' => 'password1234',
        ])->assertOk();
        $this->withCookie("beaulab_{$actor}_session", $login->getCookie("beaulab_{$actor}_session")->getValue());
        $this->app['auth']->forgetGuards();
    }
}
