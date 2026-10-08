<?php

namespace App\Domains\Common\HarmfulWord\Actions\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\HarmfulWord\Models\HarmfulWord;
use App\Domains\Common\HarmfulWord\Support\HarmfulWordRules;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class HarmfulWordManageAction
{
    public function __construct(private readonly HarmfulWordRules $rules, private readonly OperationHistoryCreateAction $history) {}

    public function create(array $data, AccountStaff $actor): array
    {
        Gate::forUser($actor)->authorize(AccessPermissions::BEAULAB_HARMFUL_WORD_MANAGE);

        return DB::transaction(function () use ($data, $actor): array {
            $analysis = $this->rules->analyze($data['text']);
            if ($analysis['invalid_count']) {
                throw ValidationException::withMessages(['text' => '입력 오류가 있는 단어를 수정해 주세요.']);
            }
            $created = 0;
            $skipped = $analysis['duplicate_count'];
            foreach ($analysis['items'] as $item) {
                if ($item['state'] !== 'NEW') {
                    continue;
                }
                $exceptions = $this->rules->exceptions($item['word'], $data['exceptions'] ?? []);
                try {
                    $word = HarmfulWord::query()->create($this->attributes($item['word'], $data));
                } catch (UniqueConstraintViolationException $exception) {
                    if (! HarmfulWord::query()->where('normalized_word', HarmfulWordRules::normalize($item['word']))->exists()) {
                        throw $exception;
                    }
                    $skipped++;

                    continue;
                }
                $this->setRelations($word, $data['scopes'], $exceptions);
                $this->record($word, OperationHistory::ACTION_CREATED, $actor);
                $created++;
            }

            return compact('created', 'skipped');
        }, 3);
    }

    public function mutate(array $data, AccountStaff $actor): void
    {
        Gate::forUser($actor)->authorize(AccessPermissions::BEAULAB_HARMFUL_WORD_MANAGE);

        try {
            DB::transaction(function () use ($data, $actor): void {
                $words = HarmfulWord::query()->whereIn('id', array_column($data['items'], 'id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                foreach ($data['items'] as $item) {
                    $word = $words->get($item['id']);
                    if (! $word || $word->lock_version !== (int) $item['lock_version']) {
                        throw ValidationException::withMessages(['items' => '다른 작업에서 금지어가 변경되었습니다. 목록에서 다시 선택해 주세요.']);
                    }
                }
                foreach ($data['items'] as $item) {
                    $word = $words->get($item['id']);
                    $before = $word->snapshot();
                    if ($data['operation'] === 'delete') {
                        $this->record($word, OperationHistory::ACTION_DELETED, $actor, $before);
                        $word->delete();

                        continue;
                    }
                    if ($data['operation'] === 'edit') {
                        $name = trim($data['word']);
                        if ($error = HarmfulWordRules::error($name)) {
                            throw ValidationException::withMessages(['word' => $error]);
                        }
                        $word->fill($this->attributes($name, $data));
                        $this->setRelations($word, $data['scopes'], $this->rules->exceptions($name, $data['exceptions'] ?? []));
                    } else {
                        $word->status = $data['operation'] === 'enable' ? 'ACTIVE' : 'INACTIVE';
                    }
                    $word->lock_version++;
                    $word->save();
                    $this->record($word, OperationHistory::ACTION_UPDATED, $actor, $before);
                }
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            if ($data['operation'] !== 'edit' || ! HarmfulWord::query()->where('normalized_word', HarmfulWordRules::normalize(trim($data['word'])))->where('id', '!=', $data['items'][0]['id'])->exists()) {
                throw $exception;
            }
            throw ValidationException::withMessages(['word' => '이미 등록된 금지어입니다.']);
        }
    }

    private function attributes(string $word, array $data): array
    {
        return [
            'word' => $word, 'normalized_word' => HarmfulWordRules::normalize($word),
            'matching_mode' => $data['matching_mode'], 'detect_variants' => $data['detect_variants'], 'status' => $data['status'],
        ];
    }

    private function setRelations(HarmfulWord $word, array $scopes, array $exceptions): void
    {
        $word->scopes()->delete();
        $word->scopes()->createMany(array_map(fn ($scope) => ['scope' => $scope], $scopes));
        $word->exceptions()->delete();
        $word->exceptions()->createMany($exceptions);
        $word->load(['scopes', 'exceptions']);
    }

    private function record(HarmfulWord $word, string $action, AccountStaff $actor, ?array $before = null): void
    {
        $this->history->execute($word, $action, $actor, metadata: [
            'before' => $before,
            'after' => $action === OperationHistory::ACTION_DELETED ? null : $word->snapshot(),
        ]);
    }
}
