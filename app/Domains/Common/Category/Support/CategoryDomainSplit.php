<?php

namespace App\Domains\Common\Category\Support;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryTopic;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** 기존 의료 분류를 업무별로 한 번 분리한다. 이후 변경은 다시 복사하지 않는다. */
final class CategoryDomainSplit
{
    public const TARGETS = [
        Category::DOMAIN_HOSPITAL_SUBJECT => [Hospital::class, 'HOSPITAL_SUBJECT'],
        Category::DOMAIN_DOCTOR_SUBJECT => [HospitalDoctor::class, 'DOCTOR_SUBJECT'],
        Category::DOMAIN_HOSPITAL_REVIEW => [HospitalReview::class, 'HOSPITAL_REVIEW'],
        Category::DOMAIN_HOSPITAL_VIDEO => [HospitalVideo::class, 'HOSPITAL_VIDEO_CATEGORY'],
        Category::DOMAIN_BEAUTY_EXPERT => [\App\Domains\BeautyExpert\Models\BeautyExpert::class, null],
    ];

    public function apply(): array
    {
        return DB::transaction(function () {
            $source = Category::where('domain', Category::DOMAIN_HOSPITAL_MEDICAL)->orderBy('depth')->orderBy('id')->lockForUpdate()->get();
            if ($source->isEmpty()) {
                return ['changed' => false, 'reason' => 'empty'];
            }
            if (DB::table('category_domain_mappings')->exists()) {
                return ['changed' => false, 'reason' => 'already_applied'];
            }
            if (Category::whereIn('domain', array_keys(self::TARGETS))->exists()) {
                throw new RuntimeException('독립 분류가 이미 존재합니다. 대응표 없이 덮어쓸 수 없습니다.');
            }
            $source->load('usages');
            $beautySource = Category::where('domain', Category::DOMAIN_BEAUTY)->orderBy('depth')->orderBy('id')->lockForUpdate()->get();
            $before = $this->snapshot();
            $maps = [];
            foreach (self::TARGETS as $domain => [$model, $usage]) {
                $map = [];
                foreach ($domain === Category::DOMAIN_BEAUTY_EXPERT ? $beautySource : $source as $row) {
                    if ($row->group_code === 'PROMOTION') {
                        continue;
                    }
                    $flat = in_array($domain, [Category::DOMAIN_HOSPITAL_SUBJECT, Category::DOMAIN_DOCTOR_SUBJECT], true);
                    if ($flat && $row->parent_id !== null) {
                        continue;
                    }
                    $scope = $usage === 'HOSPITAL_REVIEW' ? $usage.'_'.$row->group_code : $usage;
                    $values = $usage === null ? $row->only(['name', 'sort_order', 'status', 'is_menu_visible']) : $this->values($row, $scope);
                    $parent = $row->parent_id ? Category::findOrFail($map[$row->parent_id]) : null;
                    $copy = Category::withoutEvents(fn () => Category::create([
                        ...$values, 'domain' => $domain, 'code' => $row->code,
                        'parent_id' => $parent?->id, 'depth' => $row->depth, 'group_code' => $row->group_code,
                        'full_path' => $parent ? $parent->full_path.' > '.$values['name'] : $values['name'],
                    ]));
                    $map[$row->id] = $copy->id;
                    DB::table('category_domain_mappings')->insert(['legacy_id' => $row->id, 'domain' => $domain, 'category_id' => $copy->id]);
                }
                $type = (new $model)->getMorphClass();
                $assignments = DB::table('category_assignments')->where('categorizable_type', $type)->lockForUpdate()->get();
                foreach ($assignments as $assignment) {
                    if (! isset($map[$assignment->category_id])) {
                        throw new RuntimeException("{$domain} 연결 #{$assignment->id}의 분류를 보존할 수 없습니다.");
                    }
                    DB::table('category_assignments')->where('id', $assignment->id)->update(['category_id' => $map[$assignment->category_id]]);
                }
                $maps[$domain] = $map;
            }

            $hospitalMap = $maps[Category::DOMAIN_HOSPITAL_SUBJECT];
            foreach (DB::table('remote_consultation_categories')->lockForUpdate()->get() as $row) {
                if (! isset($hospitalMap[$row->category_id])) {
                    throw new RuntimeException('비대면 상담의 기존 관심 진료과목을 보존할 수 없습니다.');
                }
                DB::table('remote_consultation_categories')->where('remote_consultation_id', $row->remote_consultation_id)
                    ->where('category_id', $row->category_id)->update(['category_id' => $hospitalMap[$row->category_id]]);
            }
            // 견적·이력 JSON은 당시 이름과 ID를 기록한 스냅샷이므로 수정하지 않는다.
            foreach (['review' => Category::DOMAIN_HOSPITAL_REVIEW, 'video' => Category::DOMAIN_HOSPITAL_VIDEO] as $prefix => $domain) {
                $this->remapPositions($prefix, $maps[$domain]);
            }

            $rootTopics = [];
            foreach ($source->concat($beautySource) as $row) {
                if ($row->parent_id === null && $row->group_code !== 'PROMOTION') {
                    $topic = CategoryTopic::create(['code' => $row->code ?: 'TOPIC_'.$row->id, 'name' => $row->name,
                        'group_code' => $row->group_code, 'sort_order' => $row->sort_order, 'is_menu_visible' => true]);
                    $rootTopics[$row->id] = $topic->id;
                }
                if ($row->parent_id && isset($rootTopics[$row->parent_id])) {
                    $rootTopics[$row->id] = $rootTopics[$row->parent_id];
                }
                if (isset($rootTopics[$row->id])) {
                    foreach ([$row->id, ...array_values(array_filter(array_map(fn ($map) => $map[$row->id] ?? null, $maps)))] as $categoryId) {
                        DB::table('category_topic_links')->insert(['topic_id' => $rootTopics[$row->id], 'category_id' => $categoryId]);
                    }
                }
            }
            // 이벤트의 ID를 유지해 단가·광고·배너·예약의 참조를 변경하지 않는다.
            foreach ($source as $row) {
                $values = $this->values($row, 'HOSPITAL_EVENT_'.$row->group_code);
                $parent = $row->parent_id ? $source->firstWhere('id', $row->parent_id) : null;
                Category::withoutEvents(fn () => $row->update([...$values,
                    'full_path' => $parent ? $parent->full_path.' > '.$values['name'] : $values['name']]));
                DB::table('category_domain_mappings')->insert(['legacy_id' => $row->id, 'domain' => Category::DOMAIN_HOSPITAL_MEDICAL, 'category_id' => $row->id]);
            }
            $this->verify($before, $maps);
            CategoryUsageAvailability::forgetRequestCache();

            return ['changed' => true, 'copied' => array_map('count', $maps), 'topics' => count(array_unique($rootTopics)), 'assignments' => count($before['category_assignments'])];
        });
    }

    private function values(Category $row, string $usage): array
    {
        $setting = $row->usages->firstWhere('usage', $usage);
        if (! $setting && in_array($usage, ['HOSPITAL_SUBJECT', 'DOCTOR_SUBJECT'], true)) {
            $setting = $row->usages->firstWhere('usage', 'HOSPITAL_DOCTOR_SUBJECT');
        }
        $enabled = $setting ? $setting->status === 'ACTIVE' : $row->parent_id !== null;

        return ['name' => $setting?->display_name ?: $row->name,
            'sort_order' => $setting?->sort_order ?? $row->sort_order,
            'status' => $row->status === 'ACTIVE' && $enabled ? 'ACTIVE' : 'INACTIVE',
            'is_menu_visible' => $row->is_menu_visible && ($setting?->is_menu_visible ?? true)];
    }

    private function remapPositions(string $prefix, array $map): void
    {
        $table = $prefix.'_exposure_positions';
        foreach (DB::table($table)->orderBy('key')->lockForUpdate()->get() as $row) {
            if (! str_contains($row->key, ':')) {
                continue;
            }
            [$placement, $oldId] = explode(':', $row->key, 2);
            if (! isset($map[(int) $oldId])) {
                throw new RuntimeException("{$prefix} 노출 위치 {$row->key}의 분류를 보존할 수 없습니다.");
            }
            $newKey = $placement.':'.$map[(int) $oldId];
            DB::table($table)->insert([...get_object_vars($row), 'key' => $newKey]);
            DB::table($prefix.'_exposures')->where('position_key', $row->key)->update(['position_key' => $newKey]);
            DB::table($prefix.'_exposure_metrics')->where('position_key', $row->key)->update(['position_key' => $newKey]);
            DB::table($table)->where('key', $row->key)->delete();
        }
    }

    private function snapshot(): array
    {
        $tables = [
            'category_assignments', 'remote_consultation_categories', 'remote_consultation_quotes',
            'consultation_pricing_scopes', 'consultation_pricing_versions', 'hospital_event_ads',
            'banner_exposures', 'event_exposures',
            'review_exposure_positions', 'review_exposures', 'review_exposure_metrics',
            'video_exposure_positions', 'video_exposures', 'video_exposure_metrics',
        ];
        $result = [];
        foreach ($tables as $table) {
            $result[$table] = DB::table($table)->lockForUpdate()->get()->map(fn ($row) => (array) $row)->all();
        }

        return $result;
    }

    private function verify(array $before, array $maps): void
    {
        $after = $this->snapshot();
        $typeDomains = [];
        foreach (self::TARGETS as $domain => [$model]) {
            $typeDomains[(new $model)->getMorphClass()] = $domain;
        }
        foreach ($before as $table => $rows) {
            $expected = array_map(function ($row) use ($table, $maps, $typeDomains) {
                if ($table === 'category_assignments') {
                    $domain = $typeDomains[$row['categorizable_type']] ?? null;
                    if ($domain) {
                        $row['category_id'] = $maps[$domain][$row['category_id']];
                    }
                } elseif ($table === 'remote_consultation_categories') {
                    $row['category_id'] = $maps[Category::DOMAIN_HOSPITAL_SUBJECT][$row['category_id']];
                } elseif (str_starts_with($table, 'review_') || str_starts_with($table, 'video_')) {
                    $key = str_ends_with($table, '_positions') ? 'key' : 'position_key';
                    if (str_contains($row[$key], ':')) {
                        [$placement, $id] = explode(':', $row[$key], 2);
                        $domain = str_starts_with($table, 'review_') ? Category::DOMAIN_HOSPITAL_REVIEW : Category::DOMAIN_HOSPITAL_VIDEO;
                        $row[$key] = $placement.':'.$maps[$domain][(int) $id];
                    }
                }

                return json_encode($row, JSON_THROW_ON_ERROR);
            }, $rows);
            $actual = array_map(fn ($row) => json_encode($row, JSON_THROW_ON_ERROR), $after[$table]);
            sort($expected);
            sort($actual);
            if ($expected !== $actual) {
                throw new RuntimeException("{$table} 전체 행 보존 검증에 실패했습니다.");
            }
        }
    }
}
