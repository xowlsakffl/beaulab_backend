<?php

namespace App\Console\Commands;

use App\Common\Support\DevelopmentDataGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/** 기존 로컬 개발 DB의 영문 표시값만 보정하며 계정 식별자와 업무 상태는 보존한다. */
final class NormalizeKoreanDevelopmentData extends Command
{
    protected $signature = 'development:normalize-korean {--apply : 백업 후 기존 표시값을 변경합니다}';

    protected $description = '로컬 개발 데이터의 영문 표시값을 백업하고 한국어로 보정합니다';

    public function handle(): int
    {
        if (! $this->option('apply')) {
            $this->warn('기존 표시값을 변경하는 작업입니다. 대상 DB를 확인한 뒤 --apply를 지정해 주세요.');

            return self::FAILURE;
        }
        DevelopmentDataGuard::assertEnabled();
        if (! app()->environment('local')) {
            throw new RuntimeException('기존 표시값 보정은 로컬 개발 환경에서만 실행할 수 있습니다.');
        }

        $people = ['김서연', '이민준', '박지우', '최수빈', '정도윤', '강하윤', '윤서준', '한지민'];
        $person = static fn (int $id): string => $people[$id % count($people)];
        $fields = [
            'hospitals' => ['name' => fn ($id) => "뷰랩{$id}의원", 'description' => '개인별 상태에 맞는 진료 계획과 회복 과정을 안내합니다.', 'address' => '서울특별시 강남구 테헤란로 123', 'address_detail' => '3층', 'consulting_hours' => '평일 오전 10시부터 오후 7시까지 상담 가능합니다.', 'direction' => '지하철역에서 도보 5분 거리입니다.'],
            'hospital_doctors' => ['name' => $person, 'educations' => ['임상 진료 교육 과정 수료'], 'careers' => ['서울 의료원 근무'], 'etc_contents' => ['대한의학회 정회원']],
            'account_users' => ['name' => $person],
            'account_staffs' => ['name' => $person, 'department' => '운영팀', 'job_title' => '담당자'],
            'account_hospitals' => ['name' => $person],
            'account_beauties' => ['name' => $person],
            'beauties' => ['name' => fn ($id) => "뷰랩 뷰티 {$id}", 'description' => '개인별 피부 상태에 맞는 관리 프로그램을 제공합니다.', 'address' => '서울특별시 강남구 테헤란로 123', 'address_detail' => '3층', 'consulting_hours' => '평일 오전 10시부터 오후 7시까지 상담 가능합니다.', 'direction' => '건물 지하 주차장을 이용해 주세요.'],
            'beauty_experts' => ['name' => $person, 'educations' => ['피부 관리 전문 교육 수료'], 'careers' => ['뷰랩 피부관리실 근무'], 'etc_contents' => ['피부 관리 전문가 과정 이수']],
            'hospital_business_registrations' => ['company_name' => fn ($id) => "뷰랩{$id}의원", 'ceo_name' => $person, 'settlement_account_holder' => $person, 'business_address' => '서울특별시 강남구 테헤란로 123', 'business_address_detail' => '3층'],
            'hospital_event_dbs' => ['name' => $person],
            'hospital_event_real_model_dbs' => ['name' => $person, 'application_reason' => '관심 있는 시술의 상담과 경과 기록에 참여하고 싶습니다.', 'inquiry' => '방문 일정과 참여 시 주의사항을 안내해 주세요.'],
            'hospital_events' => ['name' => fn ($id) => "맞춤 상담 이벤트 {$id}"],
            'hospital_reviews' => ['title' => '상담부터 회복까지의 후기입니다', 'content' => '상담에서 시술 방법과 회복 과정을 자세히 안내받았습니다. 안내받은 주의사항을 지키며 경과를 확인하고 있습니다.'],
            'hospital_review_comments' => ['content' => '자세한 후기 공유 감사합니다. 회복 과정도 궁금합니다.'],
            'hospital_evaluations' => ['content' => '직원 안내가 친절했고 진료 과정과 예상 비용에 대한 설명을 충분히 들었습니다.', 'receipt_rejection_reason_text' => '진료 항목과 결제 내용을 확인할 수 있는 영수증을 다시 첨부해 주세요.'],
            'hospital_videos' => ['title' => '상담에서 자주 묻는 질문', 'description' => '시술 과정과 회복 기간 중 주의사항을 설명합니다.'],
            'talks' => ['title' => '상담 전에 준비할 사항이 궁금해요', 'content' => '상담을 예약했는데 미리 준비할 사항이 있을까요? 경험을 공유해 주세요.'],
            'talk_comments' => ['content' => '자세한 경험 공유 감사합니다. 상담 때 참고하겠습니다.'],
            'remote_consultations' => ['name' => $person, 'desired_style' => '자연스러운 변화와 균형 있는 모습을 원합니다.', 'inquiry' => '회복 기간과 시술 후 주의사항이 궁금합니다.'],
            'remote_consultation_quotes' => ['hospital_name' => fn ($id) => "뷰랩{$id}의원", 'answer' => '정확한 시술 방법과 비용은 내원 상담 후 안내드립니다.'],
        ];

        DB::transaction(function () use ($fields): void {
            $changes = [];
            foreach ($fields as $table => $columns) {
                if (! Schema::hasTable($table)) {
                    continue;
                }
                $columns = array_intersect_key($columns, array_flip(Schema::getColumnListing($table)));
                foreach (DB::table($table)->orderBy('id')->lockForUpdate()->get() as $row) {
                    $before = $after = [];
                    foreach ($columns as $column => $replacement) {
                        $value = $row->{$column};
                        if (! is_string($value) || ! preg_match('/[a-zA-Z]/', $value)) {
                            continue;
                        }
                        $isName = in_array($column, ['name', 'hospital_name', 'company_name', 'ceo_name', 'settlement_account_holder'], true);
                        $decoded = is_array($replacement) ? json_decode($value, true) : $value;
                        // 한글 본문의 정상적인 의학 약어·URL은 그대로 둔다.
                        if (! $isName && ! is_array($replacement) && preg_match('/[가-힣]/u', $value)
                            && ! preg_match('/[A-Za-z]{2,}(?:[ ,.-]+[A-Za-z]{2,}){3,}/', $value)) {
                            continue;
                        }
                        $newValue = is_callable($replacement) ? $replacement((int) $row->id) : $replacement;
                        if (is_array($replacement)) {
                            if (! is_array($decoded)) {
                                continue;
                            }
                            $newValue = json_encode(array_map(static fn ($item) => is_string($item) && preg_match('/[a-zA-Z]/', $item) ? $replacement[0] : $item, $decoded), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                        }
                        if (in_array($table, ['remote_consultation_quotes', 'hospital_business_registrations'], true) && in_array($column, ['hospital_name', 'company_name'], true)) {
                            $hospitalChange = $changes['hospitals'][(int) $row->hospital_id]['after']['name'] ?? null;
                            $newValue = $hospitalChange ?? DB::table('hospitals')->where('id', $row->hospital_id)->value('name') ?? $newValue;
                        }
                        if ($newValue !== $value) {
                            $before[$column] = $value;
                            $after[$column] = $newValue;
                        }
                    }
                    if ($after !== []) {
                        $changes[$table][$row->id] = compact('before', 'after');
                    }
                }
            }

            if ($changes === []) {
                $this->info('보정할 영문 표시값이 없습니다.');

                return;
            }
            $directory = storage_path('app/private/maintenance-backups');
            if (! is_dir($directory) && ! mkdir($directory, 0700, true)) {
                throw new RuntimeException('백업 디렉터리를 만들지 못했습니다.');
            }
            $path = $directory.'/korean-display-'.date('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json';
            $payload = json_encode(['database' => DB::getDatabaseName(), 'changes' => $changes], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            $oldMask = umask(0077);
            try {
                if (file_put_contents($path, $payload, LOCK_EX) !== strlen($payload)) {
                    throw new RuntimeException('변경 전 데이터 백업에 실패했습니다.');
                }
            } finally {
                umask($oldMask);
            }
            foreach ($changes as $table => $rows) {
                foreach ($rows as $id => $change) {
                    DB::table($table)->where('id', $id)->update($change['after']);
                }
                $this->info($table.': '.count($rows).'건 보정');
            }
            $this->info('변경 전·후 값은 비공개 maintenance-backups 디렉터리에 백업했습니다.');
        });

        return self::SUCCESS;
    }
}
