<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\HospitalChatBot;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQnaFolder;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Spatie\Permission\Models\Permission;

final class HospitalChatBotQnaFolderFlowTest extends TestCase
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

    public function test_folders_nest_two_levels_and_no_deeper(): void
    {
        $this->loginStaff();

        $parentId = (int) $this->postJson('/api/v1/staff/hospital-chatbot/qna-folders', ['name' => '병원회원'])
            ->assertOk()
            ->json('data.0.id');

        $this->postJson('/api/v1/staff/hospital-chatbot/qna-folders', [
            'name' => '가입',
            'parent_id' => $parentId,
        ])->assertOk();

        $tree = $this->getJson('/api/v1/staff/hospital-chatbot/qna-folders')->assertOk()->json('data');

        self::assertSame('병원회원', $tree[0]['name']);
        self::assertSame(1, $tree[0]['depth']);
        self::assertSame('가입', $tree[0]['children'][0]['name']);
        self::assertSame(2, $tree[0]['children'][0]['depth']);

        // 2단이 끝이다. 소분류 아래에는 만들 수 없다.
        $childId = (int) $tree[0]['children'][0]['id'];
        $this->postJson('/api/v1/staff/hospital-chatbot/qna-folders', [
            'name' => '더 깊게',
            'parent_id' => $childId,
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'INVALID_REQUEST');
    }

    public function test_sibling_names_cannot_collide_but_different_parents_can_reuse_a_name(): void
    {
        $this->loginStaff();

        $firstId = (int) $this->postJson('/api/v1/staff/hospital-chatbot/qna-folders', ['name' => '병원회원'])
            ->assertOk()->json('data.0.id');
        $secondId = (int) collect(
            $this->postJson('/api/v1/staff/hospital-chatbot/qna-folders', ['name' => '이벤트'])->assertOk()->json('data')
        )->firstWhere('name', '이벤트')['id'];

        // 같은 상위 아래 같은 이름은 고르는 사람이 구분할 수 없다.
        $this->postJson('/api/v1/staff/hospital-chatbot/qna-folders', ['name' => '병원회원'])
            ->assertStatus(422)
            ->assertJsonPath('error.message', "같은 위치에 '병원회원' 폴더가 이미 있습니다.");

        // 상위가 다르면 같은 이름을 써도 된다.
        $this->postJson('/api/v1/staff/hospital-chatbot/qna-folders', ['name' => '등록', 'parent_id' => $firstId])
            ->assertOk();
        $this->postJson('/api/v1/staff/hospital-chatbot/qna-folders', ['name' => '등록', 'parent_id' => $secondId])
            ->assertOk();

        self::assertSame(2, HospitalChatBotQnaFolder::query()->where('name', '등록')->count());
    }

    public function test_a_folder_can_only_be_deleted_when_it_is_empty(): void
    {
        $this->loginStaff();

        $parentId = (int) $this->postJson('/api/v1/staff/hospital-chatbot/qna-folders', ['name' => '병원회원'])
            ->assertOk()->json('data.0.id');
        $childId = (int) $this->postJson('/api/v1/staff/hospital-chatbot/qna-folders', [
            'name' => '가입', 'parent_id' => $parentId,
        ])->assertOk()->json('data.0.children.0.id');

        // 하위 폴더가 있으면 지울 수 없다.
        $this->deleteJson("/api/v1/staff/hospital-chatbot/qna-folders/{$parentId}")
            ->assertStatus(422);

        $qnaId = (int) $this->postJson('/api/v1/staff/hospital-chatbot/qnas', [
            'folder_id' => $childId,
            'question' => '가입은 어떻게 하나요?',
            'answer' => '가입 답변',
        ])->assertStatus(201)->json('data.id');

        // Q&A가 있으면 지울 수 없다.
        $this->deleteJson("/api/v1/staff/hospital-chatbot/qna-folders/{$childId}")
            ->assertStatus(422);

        // 삭제된 Q&A도 센다. 되살릴 때 갈 곳이 없어지면 안 된다.
        $this->deleteJson("/api/v1/staff/hospital-chatbot/qnas/{$qnaId}")->assertOk();
        self::assertTrue(HospitalChatBotQna::onlyTrashed()->whereKey($qnaId)->exists());
        $this->deleteJson("/api/v1/staff/hospital-chatbot/qna-folders/{$childId}")
            ->assertStatus(422);

        HospitalChatBotQna::withTrashed()->whereKey($qnaId)->forceDelete();

        $this->deleteJson("/api/v1/staff/hospital-chatbot/qna-folders/{$childId}")->assertOk();
        $this->deleteJson("/api/v1/staff/hospital-chatbot/qna-folders/{$parentId}")->assertOk();

        self::assertSame(0, HospitalChatBotQnaFolder::query()->count());
    }

    public function test_a_qna_follows_the_folder_it_is_filed_under(): void
    {
        $this->loginStaff();

        $tree = $this->postJson('/api/v1/staff/hospital-chatbot/qna-folders', ['name' => '병원회원'])
            ->assertOk()->json('data');
        $memberId = (int) $tree[0]['id'];
        $eventId = (int) collect(
            $this->postJson('/api/v1/staff/hospital-chatbot/qna-folders', ['name' => '이벤트'])->assertOk()->json('data')
        )->firstWhere('name', '이벤트')['id'];

        $qnaId = (int) $this->postJson('/api/v1/staff/hospital-chatbot/qnas', [
            'folder_id' => $memberId,
            'question' => '가입은 어떻게 하나요?',
            'answer' => '가입 답변',
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.folder_id', $memberId)
            ->assertJsonPath('data.folder_name', '병원회원')
            ->json('data.id');

        // 폴더를 옮기면 분류 이름도 따라간다.
        $this->patchJson("/api/v1/staff/hospital-chatbot/qnas/{$qnaId}", ['folder_id' => $eventId])
            ->assertOk()
            ->assertJsonPath('data.folder_id', $eventId)
            ->assertJsonPath('data.folder_name', '이벤트');

        // 폴더로 목록을 좁힐 수 있다.
        $this->getJson("/api/v1/staff/hospital-chatbot/qnas?folder_id={$eventId}")
            ->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/staff/hospital-chatbot/qnas?folder_id={$memberId}")
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_renaming_a_folder_keeps_the_category_copy_in_sync(): void
    {
        $this->loginStaff();

        $folderId = (int) $this->postJson('/api/v1/staff/hospital-chatbot/qna-folders', ['name' => '병원회원'])
            ->assertOk()->json('data.0.id');

        $qnaId = (int) $this->postJson('/api/v1/staff/hospital-chatbot/qnas', [
            'folder_id' => $folderId,
            'question' => '가입은 어떻게 하나요?',
            'answer' => '가입 답변',
        ])->assertStatus(201)->json('data.id');

        $this->patchJson("/api/v1/staff/hospital-chatbot/qna-folders/{$folderId}", ['name' => '회원 관리'])
            ->assertOk();

        // category는 폴더 이름의 사본이다. 목록 정렬과 기존 필터가 옛 이름을 보면 안 된다.
        self::assertSame('회원 관리', HospitalChatBotQna::query()->whereKey($qnaId)->value('category'));
        $this->getJson("/api/v1/staff/hospital-chatbot/qnas/{$qnaId}")
            ->assertOk()
            ->assertJsonPath('data.folder_name', '회원 관리');
    }

    public function test_the_folder_count_matches_the_delete_rule(): void
    {
        $this->loginStaff();

        $folderId = (int) $this->postJson('/api/v1/staff/hospital-chatbot/qna-folders', ['name' => '병원회원'])
            ->assertOk()->json('data.0.id');
        $qnaId = (int) $this->postJson('/api/v1/staff/hospital-chatbot/qnas', [
            'folder_id' => $folderId,
            'question' => '가입은 어떻게 하나요?',
            'answer' => '가입 답변',
        ])->assertStatus(201)->json('data.id');

        $this->deleteJson("/api/v1/staff/hospital-chatbot/qnas/{$qnaId}")->assertOk();

        // 소프트 삭제된 Q&A도 삭제를 막는다. 화면에 0건으로 보이면
        // 지워지지 않는 이유를 알 수 없다.
        $this->getJson('/api/v1/staff/hospital-chatbot/qna-folders')
            ->assertOk()
            ->assertJsonPath('data.0.qna_count', 1);
        $this->deleteJson("/api/v1/staff/hospital-chatbot/qna-folders/{$folderId}")->assertStatus(422);
    }

    private function loginStaff(): void
    {
        $staff = AccountStaff::factory()->create();
        $staff->givePermissionTo(Permission::findOrCreate(AccessPermissions::COMMON_ACCESS, 'staff'));
        $staff->forceFill(['password' => 'password1234'])->save();

        $this->app['auth']->forgetGuards();
        $this->withCredentials()->withHeaders(['X-Beaulab-Client' => 'web', 'Origin' => 'http://localhost:3000']);
        $csrf = $this->getJson('/api/v1/staff/auth/csrf')->assertOk();
        $this->withCookie('beaulab_staff_session', $csrf->getCookie('beaulab_staff_session')->getValue());
        $this->withHeader('X-CSRF-TOKEN', $csrf->json('data.csrf_token'));
        $this->app['auth']->forgetGuards();

        $login = $this->postJson('/api/v1/staff/auth/login', [
            'nickname' => $staff->nickname,
            'password' => 'password1234',
        ])->assertOk();
        $this->withCookie('beaulab_staff_session', $login->getCookie('beaulab_staff_session')->getValue());
        $this->app['auth']->forgetGuards();
    }
}
