<?php

namespace Database\Seeders;

use App\Common\Support\DevelopmentDataGuard;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\HarmfulWord\Models\HarmfulWord;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\ConsultationPricing\Support\ConsultationPricingResolver;
use App\Domains\HospitalChatBot\Models\HospitalChatBotClosureDate;
use App\Domains\HospitalChatBot\Models\HospitalChatBotMessageSetting;
use App\Domains\HospitalChatBot\Models\HospitalChatBotOperatingHour;
use App\Domains\HospitalChatBot\Models\HospitalChatBotSetting;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use App\Domains\HospitalPromotion\Models\HospitalPromotion;
use App\Domains\Notice\Models\Notice;
use Database\Factories\Support\SeedMediaFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class OperationSettingsDevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        DevelopmentDataGuard::assertEnabled();
        DB::transaction(function (): void {
            $staff = AccountStaff::where('nickname', 'test_super_admin')->firstOrFail();
            $this->notices($staff);
            $this->promotions($staff);
            $this->chatbot($staff);
            $this->pricing($staff);
            $this->words();
        });
    }

    private function notices(AccountStaff $staff): void
    {
        foreach (['ALL' => '전체', 'APP_WEB' => '일반회원', 'HOSPITAL' => '병의원', 'BEAUTY' => '뷰티'] as $channel => $label) {
            foreach (['서비스 이용 안내', '점검 일정 안내', '개인정보 보호 안내', '미공개 초안'] as $index => $title) {
                $notice = Notice::withTrashed()->firstOrCreate(['title' => '[개발용] '.$label.' '.$title], [
                    'channel' => $channel, 'content' => '<p>실제 공지가 아닌 개발용 안내입니다.</p><p>문의 사항은 서비스 내 상담을 이용해 주세요.</p>',
                    'status' => $index === 3 ? 'INACTIVE' : 'ACTIVE', 'is_pinned' => $index === 0,
                    'view_count' => $index === 3 ? 0 : 30 + $index * 12, 'created_by_staff_id' => $staff->id,
                    'created_at' => now()->subDays(14 - $index), 'updated_at' => now()->subDays(14 - $index),
                ]);
                if ($notice->wasRecentlyCreated && $index === 1) {
                    app(MediaAttachDeleteAction::class)->attachOne($notice, SeedMediaFactory::image('notice-attachment'), 'attachments', 'notice', 'attachments');
                }
            }
        }
    }

    private function promotions(AccountStaff $staff): void
    {
        if (HospitalPromotion::exists()) {
            return;
        }
        foreach (['LEFT' => '왼쪽', 'RIGHT' => '오른쪽'] as $side => $label) {
            foreach ([1, 2, 3] as $slot) {
                foreach ([[-20, -10], [-2, 7], [10, 20]] as $index => [$start, $end]) {
                    $promotion = HospitalPromotion::create([
                        'title' => "[개발용] {$label} {$slot}번 프로모션 ".($index + 1),
                        'content' => '<p>병의원 파트너를 위한 개발용 프로모션입니다.</p>',
                        'side' => $side, 'slot' => $slot, 'start_date' => now('Asia/Seoul')->addDays($start)->toDateString(),
                        'end_date' => now('Asia/Seoul')->addDays($end)->toDateString(),
                        'status' => $slot === 3 ? 'INACTIVE' : 'ACTIVE',
                        'click_count' => $index === 2 ? 0 : 20 + $slot * 11,
                        'created_by_staff_id' => $staff->id, 'created_at' => now()->subDays(25),
                    ]);
                    app(MediaAttachDeleteAction::class)->attachOne($promotion, SeedMediaFactory::image('hospital-promotion'), 'banner', 'hospital-promotion', 'banner');
                }
            }
        }
    }

    private function chatbot(AccountStaff $staff): void
    {
        HospitalChatBotSetting::firstOrCreate(['id' => 1], ['is_force_closed' => false, 'public_holiday_codes' => [], 'break_starts_at' => '12:00:00', 'break_ends_at' => '13:00:00', 'updated_by_staff_id' => $staff->id]);
        foreach (range(1, 7) as $day) {
            HospitalChatBotOperatingHour::firstOrCreate(['day_of_week' => $day], [
                'is_open' => $day > 0 && $day < 6, 'opens_at' => $day > 0 && $day < 6 ? '09:00:00' : null,
                'closes_at' => $day > 0 && $day < 6 ? '18:00:00' : null, 'updated_by_staff_id' => $staff->id,
            ]);
        }
        HospitalChatBotMessageSetting::firstOrCreate(['id' => 1], HospitalChatBotMessageSetting::defaults() + ['updated_by_staff_id' => $staff->id]);
        HospitalChatBotClosureDate::firstOrCreate(['reason' => '[개발용] 정기 점검 휴무'], [
            'closure_date' => now()->addDays(30)->toDateString(), 'end_date' => now()->addDays(31)->toDateString(), 'updated_by_staff_id' => $staff->id,
        ]);
    }

    private function pricing(AccountStaff $staff): void
    {
        foreach (HospitalEventAd::placementGroups() as $group => $placements) {
            $id = DB::table('hospital_event_ad_pricing_groups')->where('group_key', $group)->value('id');
            if ($id) {
                continue;
            }
            $id = DB::table('hospital_event_ad_pricing_groups')->insertGetId(['group_key' => $group, 'created_at' => now(), 'updated_at' => now()]);
            foreach ([-1, 7, 14] as $days) {
                DB::table('hospital_event_ad_pricing_versions')->insert([
                    'group_id' => $id, 'prices' => json_encode(array_fill_keys($placements, 0)),
                    'effective_at' => now()->addDays($days), 'cancelled_at' => $days === 14 ? now() : null,
                    'created_by' => $staff->id, 'cancelled_by' => $days === 14 ? $staff->id : null,
                    'created_at' => now()->subDays(2), 'updated_at' => now(),
                ]);
            }
        }
        foreach (['SURGERY', 'TREATMENT', 'PROMOTION'] as $division) {
            foreach (Category::where('domain', Category::DOMAIN_HOSPITAL_MEDICAL)->where('group_code', $division)->whereNull('parent_id')->orderBy('id')->take(2)->get() as $index => $category) {
                $key = "EVENT:{$division}:{$category->id}";
                if (DB::table('consultation_pricing_scopes')->where('scope_key', $key)->exists()) {
                    continue;
                }
                $id = DB::table('consultation_pricing_scopes')->insertGetId(['scope_key' => $key, 'created_at' => now(), 'updated_at' => now()]);
                foreach ([-1, 7, 14] as $days) {
                    DB::table('consultation_pricing_versions')->insert([
                        'scope_id' => $id, 'mode' => $index === 0 ? 'CUSTOM' : 'DEFAULT',
                        'ranges' => $index === 0 ? json_encode(ConsultationPricingResolver::defaultRanges()) : null,
                        'effective_at' => now()->addDays($days), 'cancelled_at' => $days === 14 ? now() : null,
                        'created_by' => $staff->id, 'cancelled_by' => $days === 14 ? $staff->id : null,
                        'created_at' => now()->subDays(2), 'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    private function words(): void
    {
        foreach (['ADJECTIVE' => '조용한', 'NOUN' => '시험구름'] as $type => $word) {
            \App\Domains\Nickname\Models\NicknameWord::firstOrCreate(['type' => $type, 'word' => $word], ['status' => 'INACTIVE']);
        }
        foreach (['차단시험어', '독립시험어', '중지시험어'] as $index => $word) {
            $rule = HarmfulWord::firstOrCreate(['normalized_word' => $word], [
                'word' => $word, 'matching_mode' => $index === 1 ? 'STANDALONE' : 'CONTAINS',
                'detect_variants' => $index === 0, 'status' => $index === 2 ? 'INACTIVE' : 'ACTIVE',
            ]);
            if (! $rule->wasRecentlyCreated) {
                continue;
            }
            foreach (HarmfulWord::SCOPES as $scope) {
                $rule->scopes()->create(['scope' => $scope]);
            }
            if ($index === 0) {
                $rule->exceptions()->create(['phrase' => '차단시험어해설', 'normalized_phrase' => '차단시험어해설']);
            }
        }
    }
}
