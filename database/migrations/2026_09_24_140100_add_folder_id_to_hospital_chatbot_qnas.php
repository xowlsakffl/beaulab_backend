<?php

use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQnaFolder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hospital_chatbot_qnas', function (Blueprint $table) {
            $table->unsignedBigInteger('folder_id')->nullable()->after('id')->comment('속한 Q&A 폴더 ID');

            $table->foreign('folder_id', 'hcq_folder_fk')
                ->references('id')->on('hospital_chatbot_qna_folders')->nullOnDelete();
            $table->index(['folder_id', 'status', 'sort_order', 'id'], 'hcq_folder_status_sort_index');
        });

        // 기존 category 문자열을 1단 폴더로 옮긴다. 값이 없으면 "기타"로 모은다.
        $categories = HospitalChatBotQna::query()
            ->withTrashed()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        foreach ($categories as $index => $category) {
            $name = is_string($category) && trim($category) !== '' ? trim($category) : '기타';

            $folder = HospitalChatBotQnaFolder::query()->firstOrCreate(
                ['parent_id' => null, 'name' => $name],
                ['depth' => 1, 'sort_order' => $index],
            );

            HospitalChatBotQna::query()
                ->withTrashed()
                ->where('category', $category)
                ->update(['folder_id' => $folder->id]);
        }
    }

    public function down(): void
    {
        Schema::table('hospital_chatbot_qnas', function (Blueprint $table) {
            $table->dropForeign('hcq_folder_fk');
            $table->dropIndex('hcq_folder_status_sort_index');
            $table->dropColumn('folder_id');
        });
    }
};
