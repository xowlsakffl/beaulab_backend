<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQnaFolder;
use App\Domains\HospitalChatBot\Queries\HospitalChatBotQnaTreeQuery;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** 이미 마이그레이션된 개인 로컬 DB를 초기화하지 않고 새 트리로 전환한다. */
final class ConvertHospitalChatBotQnaTree extends Command
{
    protected $signature = 'hospital-chatbot:convert-qna-tree {--apply : 로컬 DB에 실제 반영}';

    protected $description = '기존 Q&A 폴더를 대화 선택 항목으로 전환합니다';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing']) || ! in_array(config('database.connections.mysql.host'), ['127.0.0.1', 'localhost'], true)) {
            $this->error('개인 로컬 DB에서만 실행할 수 있습니다.');

            return self::FAILURE;
        }
        $this->info('대상 DB: '.DB::connection()->getDatabaseName());
        $this->info('연결된 기존 질문: '.HospitalChatBotQna::withTrashed()->whereNotNull('folder_id')->count());
        if (! $this->option('apply')) {
            return self::SUCCESS;
        }
        $backup = storage_path('app/private/qna-tree-backup-'.now()->format('Ymd-His-u').'.json');
        if (! is_dir(dirname($backup))) {
            mkdir(dirname($backup), 0700, true);
        }
        $written = file_put_contents($backup, json_encode([
            'qnas' => DB::table('hospital_chatbot_qnas')->get(),
            'folders' => DB::table('hospital_chatbot_qna_folders')->get(),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        if ($written === false) {
            throw new \RuntimeException('백업 파일을 만들지 못해 전환을 중단합니다.');
        }
        chmod($backup, 0600);
        $this->info('전환 전 백업: '.$backup);
        if (! Schema::hasColumn('hospital_chatbot_qnas', 'parent_id')) {
            Schema::table('hospital_chatbot_qnas', function (Blueprint $table): void {
                $table->foreignId('parent_id')->nullable()->comment('상위 선택 항목 ID')->constrained('hospital_chatbot_qnas')->restrictOnDelete();
            });
        }
        if (! Schema::hasColumn('hospital_chatbot_qnas', 'depth')) {
            Schema::table('hospital_chatbot_qnas', fn (Blueprint $table) => $table->unsignedTinyInteger('depth')->default(1)->comment('대화 선택 단계 1~3'));
        }
        DB::transaction(function (): void {
            $old = HospitalChatBotQna::withTrashed()->whereNotNull('folder_id')->lockForUpdate()->get();
            if ($old->isEmpty()) {
                $this->normalizeKeys();

                return;
            }
            $map = [];
            foreach (HospitalChatBotQnaFolder::query()->orderBy('depth')->orderBy('id')->get() as $folder) {
                $parentId = $folder->parent_id !== null ? ($map[$folder->parent_id] ?? null) : null;
                $key = HospitalChatBotQnaTreeQuery::questionKey($folder->name, $parentId);
                $node = HospitalChatBotQna::query()->firstOrCreate(['question_key' => $key], [
                    'parent_id' => $parentId, 'depth' => $folder->depth, 'category' => '',
                    'question' => $folder->name, 'answer' => '', 'sort_order' => $folder->sort_order,
                    'status' => 'ACTIVE', 'created_by_staff_id' => $folder->created_by_staff_id,
                    'updated_by_staff_id' => $folder->updated_by_staff_id,
                ]);
                $map[$folder->id] = $node->id;
            }
            foreach ($old as $qna) {
                $parentId = $map[$qna->folder_id] ?? null;
                if ($parentId === null) {
                    throw new \RuntimeException('연결된 기존 폴더를 찾지 못했습니다. 전환을 취소합니다.');
                }
                $parent = HospitalChatBotQna::query()->findOrFail($parentId);
                $qna->forceFill(['parent_id' => $parentId, 'depth' => $parent->depth + 1,
                    'question_key' => HospitalChatBotQnaTreeQuery::questionKey($qna->question, $parentId),
                    'folder_id' => null, 'category' => '',
                ])->save();
            }
            $this->normalizeKeys();
        });
        $this->info('기존 질문·답변을 보존하여 전환했습니다. 이전 폴더 테이블은 복구용으로 유지합니다.');

        return self::SUCCESS;
    }

    private function normalizeKeys(): void
    {
        foreach (HospitalChatBotQna::withTrashed()->lockForUpdate()->get() as $qna) {
            $key = HospitalChatBotQna::questionKey($qna->question, $qna->parent_id);
            if ($qna->question_key !== $key) {
                $qna->forceFill(['question_key' => $key])->save();
            }
        }
    }
}
