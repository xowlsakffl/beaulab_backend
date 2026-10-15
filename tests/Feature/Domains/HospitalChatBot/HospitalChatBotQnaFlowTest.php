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
    use RefreshDatabase, \Tests\Support\WithHospitalDepartment;

    public function createApplication(): Application
    {
        $app = parent::createApplication();

        if ($app->configurationIsCached() || config('database.connections.mysql.database') !== 'beaulab_testing') {
            throw new \LogicException('Feature tests require uncached configuration and the beaulab_testing database.');
        }

        return $app;
    }

    public function test_legacy_folder_routes_are_not_registered(): void
    {
        foreach (app('router')->getRoutes() as $route) {
            self::assertStringNotContainsString('hospital-chatbot/qna-folders', $route->uri());
        }
    }

    public function test_legacy_folder_input_is_rejected_instead_of_silently_ignored(): void
    {
        $staff = AccountStaff::factory()->create();
        foreach ([AccessPermissions::BEAULAB_CHATBOT_QNA_SHOW, AccessPermissions::BEAULAB_CHATBOT_QNA_CREATE, AccessPermissions::BEAULAB_CHATBOT_QNA_UPDATE, AccessPermissions::BEAULAB_CHATBOT_QNA_DELETE] as $permission) {
            $staff->givePermissionTo(Permission::findOrCreate($permission, 'staff'));
        }
        $this->login('staff', $staff, 'http://localhost:3000');
        $this->postJson('/api/v1/staff/hospital-chatbot/qnas', [
            'folder_id' => 1, 'question' => '이전 폴더 등록',
        ])->assertStatus(422);

        $id = $this->postJson('/api/v1/staff/hospital-chatbot/qnas', [
            'question' => '상위 항목',
        ])->assertStatus(201)->json('data.id');
        $this->patchJson("/api/v1/staff/hospital-chatbot/qnas/{$id}", [
            'folder_id' => 1,
        ])->assertStatus(422);
        $this->getJson("/api/v1/staff/hospital-chatbot/qnas/{$id}")
            ->assertOk()->assertJsonPath('data.parent_id', null);
    }

    public function test_staff_manages_qnas_and_hospital_only_receives_active_plain_text_fields(): void
    {
        $staff = AccountStaff::factory()->create();
        foreach ([AccessPermissions::BEAULAB_CHATBOT_QNA_SHOW, AccessPermissions::BEAULAB_CHATBOT_QNA_CREATE, AccessPermissions::BEAULAB_CHATBOT_QNA_UPDATE, AccessPermissions::BEAULAB_CHATBOT_QNA_DELETE] as $permission) {
            $staff->givePermissionTo(Permission::findOrCreate($permission, 'staff'));
        }
        $this->login('staff', $staff, 'http://localhost:3000');

        $created = $this->postJson('/api/v1/staff/hospital-chatbot/qnas', [
            'parent_id' => null,
            'question' => '계정은 어떻게 변경하나요?',
            'answer' => '<strong>계정 메뉴</strong>에서 변경해 주세요.',
            'status' => HospitalChatBotQna::STATUS_ACTIVE,
            'sort_order' => 2,
        ])->assertCreated();

        $qnaId = (int) $created->json('data.id');

        $this->postJson('/api/v1/staff/hospital-chatbot/qnas', [
            'parent_id' => null,
            'question' => '  계정은   어떻게 변경하나요? ',
            'answer' => '중복 답변',
        ])->assertUnprocessable()
            ->assertJsonPath('error.details.errors.question.0', '같은 상위 항목에 동일한 질문이 있습니다.');

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

    public function test_tree_enforces_depth_cycles_duplicates_and_child_deletion(): void
    {
        $staff = AccountStaff::factory()->create();
        foreach ([AccessPermissions::BEAULAB_CHATBOT_QNA_SHOW, AccessPermissions::BEAULAB_CHATBOT_QNA_CREATE, AccessPermissions::BEAULAB_CHATBOT_QNA_UPDATE, AccessPermissions::BEAULAB_CHATBOT_QNA_DELETE] as $permission) {
            $staff->givePermissionTo(Permission::findOrCreate($permission, 'staff'));
        }
        $this->login('staff', $staff, 'http://localhost:3000');
        $create = fn (string $question, ?int $parent = null) => $this->postJson('/api/v1/staff/hospital-chatbot/qnas', [
            'question' => $question, 'parent_id' => $parent, 'answer' => '',
        ])->assertCreated()->json('data.id');
        $root = $create('의료진 프로필');
        $child = $create('사진 변경', $root);
        $leaf = $create('등록 규격', $child);
        $other = $create('병의원 프로필');
        $create('사진 변경', $other);
        $this->postJson('/api/v1/staff/hospital-chatbot/qnas', ['question' => '사진 변경', 'parent_id' => $root])->assertUnprocessable();
        $this->postJson('/api/v1/staff/hospital-chatbot/qnas', ['question' => '4단계', 'parent_id' => $leaf])->assertUnprocessable();
        $this->patchJson("/api/v1/staff/hospital-chatbot/qnas/{$root}", ['parent_id' => $child])->assertUnprocessable();
        $this->patchJson("/api/v1/staff/hospital-chatbot/qnas/{$root}", ['parent_id' => $other])->assertUnprocessable();
        $this->deleteJson("/api/v1/staff/hospital-chatbot/qnas/{$root}")->assertUnprocessable();
        $this->getJson('/api/v1/staff/hospital-chatbot/qnas?depth=3')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.parent_path', '의료진 프로필 > 사진 변경');
        $this->getJson('/api/v1/staff/hospital-chatbot/qnas/tree')->assertOk()->assertJsonCount(5, 'data');
        $this->getJson("/api/v1/staff/hospital-chatbot/qnas?root_id={$root}&per_page=1&page=2")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.total', 3)->assertJsonPath('data.0.id', $child);
        $this->getJson("/api/v1/staff/hospital-chatbot/qnas?root_id={$root}&depth=3")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $leaf);
        $this->getJson("/api/v1/staff/hospital-chatbot/qnas?root_id={$child}")->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/staff/hospital-chatbot/qnas?root_id=-1')->assertUnprocessable();
        $this->getJson('/api/v1/staff/hospital-chatbot/qnas?all=1&per_page=1&page=2&status=ACTIVE,INACTIVE')
            ->assertOk()->assertJsonCount(5, 'data');
        $this->patchJson("/api/v1/staff/hospital-chatbot/qnas/{$other}", ['parent_id' => null, 'position' => 0])
            ->assertOk()->assertJsonPath('data.sort_order', 0);
        $this->getJson('/api/v1/staff/hospital-chatbot/qnas?all=1')->assertJsonPath('data.0.id', $other);
        $this->patchJson("/api/v1/staff/hospital-chatbot/qnas/{$child}", ['parent_id' => $other, 'position' => 0])
            ->assertUnprocessable();
        $this->assertDatabaseHas('hospital_chatbot_qnas', ['id' => $child, 'parent_id' => $root]);
        $this->patchJson("/api/v1/staff/hospital-chatbot/qnas/{$root}", ['parent_id' => $leaf, 'position' => 0])
            ->assertUnprocessable();
        $this->patchJson("/api/v1/staff/hospital-chatbot/qnas/{$root}", ['position' => -1])->assertUnprocessable();
        $this->patchJson("/api/v1/staff/hospital-chatbot/qnas/{$child}", ['parent_id' => null, 'position' => 1])->assertOk()->assertJsonPath('data.depth', 1)->assertJsonPath('data.sort_order', 1);
        $this->assertDatabaseHas('hospital_chatbot_qnas', ['id' => $leaf, 'depth' => 2, 'parent_id' => $child]);
        $this->deleteJson("/api/v1/staff/hospital-chatbot/qnas/{$root}")->assertOk();
        $this->postJson('/api/v1/staff/hospital-chatbot/qnas', ['question' => '삭제된 상위', 'parent_id' => $root])->assertUnprocessable();
    }

    public function test_hospital_excludes_descendants_of_hidden_nodes(): void
    {
        $staff = AccountStaff::factory()->create();
        $staff->givePermissionTo(Permission::findOrCreate(AccessPermissions::BEAULAB_CHATBOT_QNA_CREATE, 'staff'));
        $staff->givePermissionTo(Permission::findOrCreate(AccessPermissions::BEAULAB_CHATBOT_QNA_SHOW, 'staff'));
        $this->actingAs($staff, 'staff');
        $create = app(\App\Domains\HospitalChatBot\Actions\Staff\HospitalChatBotQnaCreateForStaffAction::class);
        $root = $create->execute(['question' => '의료진', 'status' => 'INACTIVE']);
        $child = $create->execute(['question' => '프로필 변경', 'parent_id' => $root['id']]);
        $create->execute(['question' => '이미지 규격', 'parent_id' => $child['id']]);
        $visible = $create->execute(['question' => '병의원 정보']);
        $account = AccountHospital::factory()->forHospital(Hospital::factory()->active()->create())->active()->create();
        $this->login('hospital', $account, 'http://localhost:3002');
        $this->getJson('/api/v1/hospital/hospital-chatbot/qnas')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visible['id'])->assertJsonPath('data.0.depth', 1);
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
        if ($cookie = $csrf->getCookie("beaulab_{$actor}_session")) {
            $this->withCookie("beaulab_{$actor}_session", $cookie->getValue());
        }
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
