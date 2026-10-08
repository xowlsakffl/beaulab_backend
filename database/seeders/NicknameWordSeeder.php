<?php

namespace Database\Seeders;

use App\Domains\Nickname\Models\NicknameWord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class NicknameWordSeeder extends Seeder
{
    public function run(): void
    {
        // 초기 단어만 추가한다. 관리자가 수정한 사용 여부와 단어는 덮어쓰지 않는다.
        DB::transaction(function (): void {
            $defaults = [
                'ADJECTIVE' => ['빛나는', '따뜻한', '맑은', '행복한', '즐거운', '포근한', '다정한', '고운', '산뜻한', '싱그러운', '반짝이는', '아름다운', '소중한', '편안한', '활기찬', '상쾌한', '기분좋은', '눈부신', '사랑스러운', '은은한'],
                'NOUN' => ['하늘', '봄', '햇살', '구름', '별', '바다', '산책', '미소', '아침', '노을', '새벽', '여름', '가을', '겨울', '꽃', '숲', '바람', '달빛', '여행', '꿈'],
            ];
            foreach ($defaults as $type => $words) {
                if (NicknameWord::query()->where('type', $type)->exists()) {
                    continue;
                }
                foreach ($words as $word) {
                    NicknameWord::query()->firstOrCreate(['type' => $type, 'word' => $word], ['status' => 'ACTIVE']);
                }
            }
        });
    }
}
