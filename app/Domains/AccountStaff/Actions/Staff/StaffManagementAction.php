<?php

namespace App\Domains\AccountStaff\Actions\Staff;

use App\Common\Authorization\AccessPermissions as P;
use App\Common\Support\PaginatedResponse;
use App\Domains\AccountStaff\Dto\Staff\StaffManagementDto;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountStaff\Queries\Staff\StaffManagementQuery;
use App\Domains\AccountStaff\Support\StaffPermissionCatalog;
use App\Domains\AccountStaff\Support\StaffPermissionSnapshot;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Dto\OperationHistoryDto;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

final class StaffManagementAction
{
    public function __construct(private readonly StaffManagementQuery $query, private readonly OperationHistoryCreateAction $history) {}

    public function list(array $filters): array
    {
        Gate::authorize('viewAny', AccountStaff::class);

        return $this->query->list($filters);
    }

    public function show(AccountStaff $staff): array
    {
        Gate::authorize('view', $staff);

        return StaffManagementDto::fromModel($staff->load('roles', 'permissions'), true);
    }

    public function options(): array
    {
        abort_unless(auth()->user()->can(P::BEAULAB_STAFF_SHOW) || auth()->user()->can(P::BEAULAB_STAFF_CREATE), 403);

        return ['roles' => collect(StaffPermissionCatalog::ROLES)->map(fn ($label, $name) => ['value' => $name, 'label' => $label])->values()->all(), 'catalog' => StaffPermissionCatalog::items(), 'templates' => $this->templates()];
    }

    private function templates(): array
    {
        return Role::where('guard_name', 'staff')->whereIn('name', array_keys(StaffPermissionCatalog::ROLES))->with('permissions')->get()->map(function ($role) {
            $names = $role->permissions->pluck('name')->sort()->values()->all();

            return ['role' => $role->name, 'permissions' => $names, 'version' => hash('sha256', json_encode($names))];
        })->all();
    }

    // 직원 권한 변경과 템플릿 변경은 같은 행을 먼저 잠가 마지막 관리자 보호를 직렬화한다.
    private function lockAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        $roles = Role::where('guard_name', 'staff')->whereIn('name', array_keys(StaffPermissionCatalog::ROLES))->orderBy('id')->lockForUpdate()->get();
        abort_if($roles->count() !== count(StaffPermissionCatalog::ROLES), 409, '역할 기본설정을 먼저 준비해 주세요.');
        $staffs = AccountStaff::withTrashed()->orderBy('id')->lockForUpdate()->get();
        foreach ($staffs as $staff) {
            StaffPermissionSnapshot::initialize($staff);
        }

        return $staffs;
    }

    private function assertGrantable(array $names, AccountStaff $actor): void
    {
        $extra = array_diff($names, $actor->getAllPermissions()->pluck('name')->all());
        if ($extra) {
            throw ValidationException::withMessages(['permissions' => '본인이 보유하지 않은 권한은 부여할 수 없습니다.']);
        }
    }

    public function save(?AccountStaff $target, array $data): array
    {
        $target ? Gate::authorize('update', $target) : Gate::authorize('create', AccountStaff::class);

        return DB::transaction(function () use ($target, $data) {
            $staffs = $this->lockAccounts();
            $actor = $staffs->firstWhere('id', auth()->id());
            $staff = $target ? $staffs->firstWhere('id', $target->id) : new AccountStaff;
            abort_unless($actor && $staff && ! $staff->trashed(), 404);
            abort_unless($actor->isActive() && $actor->can($target ? P::BEAULAB_STAFF_UPDATE : P::BEAULAB_STAFF_CREATE), 403);
            if ($target && ((int) $staff->lock_version !== (int) $data['version'] || $staff->updated_at->toISOString() !== \Illuminate\Support\Carbon::parse($data['updated_at'])->toISOString())) {
                throw ValidationException::withMessages(['updated_at' => '다른 직원이 수정했습니다. 새로고침 후 다시 저장해 주세요.']);
            }
            $before = $target ? StaffManagementDto::fromModel($staff, true) : [];
            $roleChanged = ! $target || $staff->roles->first()?->name !== $data['role'];
            $existing = $staff->getAllPermissions()->pluck('name')->sort()->values()->all();
            $names = $roleChanged
                ? Role::findByName($data['role'], 'staff')->permissions->pluck('name')->sort()->values()->all()
                : (isset($data['permissions']) ? collect($data['permissions'])->sort()->values()->all() : $existing);
            if ($roleChanged || $names !== $existing) {
                abort_unless($actor->can(P::BEAULAB_STAFF_PERMISSIONS_MANAGE), 403, '역할·권한 관리 권한이 필요합니다.');
                $this->assertGrantable($names, $actor);
            }
            $status = $target ? $data['status'] : 'ACTIVE';
            $changesAccess = ! empty($data['password'])
                || ($data['email'] ?? null) !== $staff->email
                || ($status === 'ACTIVE' && ! $staff->isActive());
            if ($target && ! $actor->is($staff) && $changesAccess) {
                abort_unless($actor->can(P::BEAULAB_STAFF_PERMISSIONS_MANAGE), 403, '다른 직원의 로그인 정보 변경에는 역할·권한 관리 권한이 필요합니다.');
                $this->assertGrantable($existing, $actor);
            }
            if ($actor->is($staff) && $status !== 'ACTIVE') {
                throw ValidationException::withMessages(['status' => '본인의 접속을 차단할 수 없습니다.']);
            }
            $this->protectLastManager($staffs, $staff, $names, $status);
            $fields = \Illuminate\Support\Arr::only($data, ['name', 'email', 'phone', 'department', 'job_title']);
            if (! $target) {
                $fields['nickname'] = $data['nickname'];
            }
            if (! empty($data['password'])) {
                $fields['password'] = $data['password'];
            }
            if (($fields['email'] ?? null) !== $staff->email) {
                $fields['email_verified_at'] = null;
            }
            $staff->fill($fields)->forceFill(['status' => $status, 'permissions_initialized_at' => $staff->permissions_initialized_at ?? now(), 'lock_version' => (int) $staff->lock_version + 1])->save();
            $staff->syncRoles([$data['role']]);
            $staff->syncPermissions($names);
            $staff->touch();
            if ($status !== 'ACTIVE' || ! empty($data['password'])) {
                $staff->tokens()->delete();
            }
            $after = StaffManagementDto::fromModel($staff->fresh(['roles', 'permissions']), true);
            $this->record($staff, $actor, $before, $after, ! $target, ! empty($data['password']));

            return $after;
        });
    }

    private function protectLastManager($staffs, AccountStaff $target, array $names, string $status): void
    {
        $required = [P::COMMON_ACCESS, P::BEAULAB_STAFF_SHOW, P::BEAULAB_STAFF_UPDATE, P::BEAULAB_STAFF_PERMISSIONS_MANAGE];
        if ($status === 'ACTIVE' && ! array_diff($required, $names)) {
            return;
        }
        $other = $staffs->contains(fn ($staff) => ! $staff->is($target) && ! $staff->trashed() && $staff->isActive() && ! array_diff($required, $staff->getAllPermissions()->pluck('name')->all()));
        if (! $other) {
            throw ValidationException::withMessages(['permissions' => '마지막 권한 관리 가능 직원의 접근 권한을 제거할 수 없습니다.']);
        }
    }

    public function delete(AccountStaff $target): void
    {
        Gate::authorize('delete', $target);
        DB::transaction(function () use ($target): void {
            $staffs = $this->lockAccounts();
            $actor = $staffs->firstWhere('id', auth()->id());
            $staff = $staffs->firstWhere('id', $target->id);
            abort_unless($actor?->isActive() && $actor->can(P::BEAULAB_STAFF_DELETE) && $staff && ! $staff->trashed(), 403);
            if ($actor->is($staff)) {
                throw ValidationException::withMessages(['staff' => '본인 계정은 삭제할 수 없습니다.']);
            }
            $this->protectLastManager($staffs, $staff, [], 'BLOCKED');
            $this->history->execute($staff, OperationHistory::ACTION_DELETED, $actor, reason: '직원 계정 삭제');
            $staff->tokens()->delete();
            $staff->delete();
        });
    }

    public function saveTemplate(array $data): array
    {
        Gate::authorize(P::BEAULAB_STAFF_PERMISSIONS_MANAGE);

        return DB::transaction(function () use ($data) {
            $staffs = $this->lockAccounts();
            $actor = $staffs->firstWhere('id', auth()->id());
            abort_unless($actor?->isActive() && $actor->can(P::BEAULAB_STAFF_PERMISSIONS_MANAGE), 403);
            $this->assertGrantable($data['permissions'], $actor);
            $before = collect($this->templates())->firstWhere('role', $data['role']);
            if (! hash_equals($before['version'], $data['version'])) {
                throw ValidationException::withMessages(['version' => '기본권한이 변경되었습니다. 새로고침 후 다시 저장해 주세요.']);
            }
            Role::findByName($data['role'], 'staff')->syncPermissions($data['permissions']);
            activity('audit')->causedBy($actor)->withProperties(['role' => $data['role'], 'before' => $before['permissions'], 'after' => $data['permissions']])->log('직원 역할 기본권한 변경');

            return $this->templates();
        });
    }

    public function histories(AccountStaff $staff, array $filters): array
    {
        Gate::authorize('view', $staff);

        return PaginatedResponse::fromPaginator($staff->operationHistories()->with('actor')->paginate($filters['operation_histories_per_page'] ?? 10, ['*'], 'operation_histories_page', $filters['operation_histories_page'] ?? 1), fn ($history) => OperationHistoryDto::fromModel($history)->toArray());
    }

    private function record(AccountStaff $staff, AccountStaff $actor, array $before, array $after, bool $created, bool $passwordChanged): void
    {
        $labels = ['name' => '이름', 'phone' => '전화번호', 'email' => '이메일', 'department' => '부서', 'job_title' => '직급', 'status' => '계정상태', 'role' => '역할', 'permissions' => '권한'];
        $changes = [];
        foreach ($labels as $key => $label) {
            if (($before[$key] ?? null) === ($after[$key] ?? null)) {
                continue;
            }
            $display = static function ($value) use ($key) {
                if ($key === 'role') {
                    return StaffPermissionCatalog::ROLES[$value] ?? '-';
                }
                if ($key === 'status') {
                    return ['ACTIVE' => '정상', 'SUSPENDED' => '정지', 'BLOCKED' => '차단'][$value] ?? '-';
                }
                if (is_array($value)) {
                    return implode(', ', array_map(static function ($name) {
                        $item = collect(StaffPermissionCatalog::items())->firstWhere('name', $name);

                        return $item ? $item['menu'].' '.$item['action'] : $name;
                    }, $value));
                }

                return $value ?: '-';
            };
            $changes[] = ['field_key' => $key, 'field_label' => $label, 'before_value' => $before[$key] ?? null, 'after_value' => $after[$key] ?? null, 'before_display' => $display($before[$key] ?? null), 'after_display' => $display($after[$key] ?? null)];
        }
        if ($passwordChanged && ! $created) {
            $changes[] = ['field_key' => 'password', 'field_label' => '비밀번호', 'before_display' => '-', 'after_display' => '변경됨'];
        }
        $this->history->execute($staff, $created ? OperationHistory::ACTION_CREATED : OperationHistory::ACTION_UPDATED, $actor, changes: $changes);
    }
}
