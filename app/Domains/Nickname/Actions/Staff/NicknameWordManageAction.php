<?php

namespace App\Domains\Nickname\Actions\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Nickname\Models\NicknameWord;
use App\Domains\Nickname\Support\NicknameWordRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class NicknameWordManageAction
{
    public function __construct(private readonly NicknameWordRules $rules, private readonly OperationHistoryCreateAction $history) {}

    public function create(array $data, AccountStaff $actor): array
    {
        Gate::forUser($actor)->authorize(AccessPermissions::BEAULAB_NICKNAME_MANAGE);

        return DB::transaction(function () use ($data, $actor): array {
            NicknameWord::query()->where('type', $data['type'])->orderBy('id')->lockForUpdate()->get();
            $analysis = $this->rules->analyze($data['type'], $data['text']);
            if ($analysis['invalid_count']) {
                throw ValidationException::withMessages(['text' => '등록할 수 없는 단어를 수정해 주세요.']);
            }
            if (! $analysis['new_count']) {
                throw ValidationException::withMessages(['text' => '새로 등록할 단어가 없습니다.']);
            }
            foreach ($analysis['items'] as $item) {
                if ($item['state'] !== 'NEW') {
                    continue;
                }
                $word = NicknameWord::query()->create(['type' => $data['type'], 'word' => $item['word'], 'status' => 'ACTIVE']);
                $this->record($word, OperationHistory::ACTION_CREATED, $actor, null);
            }

            return ['created' => $analysis['new_count'], 'skipped' => $analysis['duplicate_count']];
        }, 3);
    }

    public function mutate(array $data, AccountStaff $actor): void
    {
        Gate::forUser($actor)->authorize(AccessPermissions::BEAULAB_NICKNAME_MANAGE);

        DB::transaction(function () use ($data, $actor): void {
            $words = NicknameWord::query()->where('type', $data['type'])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $items = $data['items'];
            foreach ($items as $item) {
                $word = $words->get($item['id']);
                if (! $word || $word->lock_version !== (int) $item['lock_version']) {
                    throw ValidationException::withMessages(['words' => '다른 작업에서 단어가 변경되었습니다. 최신 목록을 확인해 주세요.']);
                }
            }
            if (in_array($data['operation'], ['disable', 'delete'], true)) {
                $remaining = $words->except(array_column($items, 'id'))->where('status', 'ACTIVE')->count();
                if ($words->where('status', 'ACTIVE')->isNotEmpty() && $remaining === 0) {
                    throw ValidationException::withMessages(['words' => '사용 중인 단어는 형용사·명사 각각 한 개 이상 남겨 주세요.']);
                }
            }
            if ($data['operation'] === 'edit') {
                $name = NicknameWordRules::normalize($data['word']);
                if ($error = NicknameWordRules::error($name)) {
                    throw ValidationException::withMessages(['word' => $error]);
                }
                if ($words->except($items[0]['id'])->contains('word', $name)) {
                    throw ValidationException::withMessages(['word' => '이미 등록된 단어입니다.']);
                }
            }
            foreach ($items as $item) {
                $word = $words->get($item['id']);
                $before = $word->only(['word', 'status']);
                if ($data['operation'] === 'delete') {
                    $this->record($word, OperationHistory::ACTION_DELETED, $actor, $before);
                    $word->delete();

                    continue;
                }
                if ($data['operation'] === 'edit') {
                    $word->word = $name;
                } else {
                    $word->status = $data['operation'] === 'enable' ? 'ACTIVE' : 'INACTIVE';
                }
                if (! $word->isDirty()) {
                    continue;
                }
                $word->lock_version++;
                $word->save();
                $this->record($word, OperationHistory::ACTION_UPDATED, $actor, $before);
            }
        }, 3);
    }

    private function record(NicknameWord $word, string $action, AccountStaff $actor, ?array $before): void
    {
        $this->history->execute($word, $action, $actor, metadata: [
            'type' => $word->type,
            'before' => $before,
            'after' => $action === OperationHistory::ACTION_DELETED ? null : $word->only(['word', 'status']),
        ]);
    }
}
