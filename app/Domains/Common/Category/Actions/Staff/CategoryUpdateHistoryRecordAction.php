<?php

namespace App\Domains\Common\Category\Actions\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use Illuminate\Database\Eloquent\Model;

final class CategoryUpdateHistoryRecordAction
{
    public function __construct(
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    /**
     * @return array<string, array{label:string,value:mixed,display:?string}>
     */
    public function capture(Category $category): array
    {
        $category->loadMissing(['parent', 'iconMedia']);

        return [
            'domain' => $this->item('도메인', $category->domain, $category->domain),
            'parent' => $this->item('상위 카테고리', $category->parent_id ? (int) $category->parent_id : null, $category->parent?->full_path ?: $category->parent?->name),
            'depth' => $this->item('단계', (int) $category->depth, (string) (int) $category->depth),
            'name' => $this->item('카테고리명', $category->name, $category->name),
            'code' => $this->item('코드', $category->code, $category->code),
            'full_path' => $this->item('전체 경로', $category->full_path, $category->full_path),
            'sort_order' => $this->item('정렬순서', (int) $category->sort_order, (string) (int) $category->sort_order),
            'status' => $this->item('상태', $category->status, $category->status),
            'is_menu_visible' => $this->item('메뉴 노출', (bool) $category->is_menu_visible, (bool) $category->is_menu_visible ? '예' : '아니오'),
            'icon' => $this->item('아이콘', $category->iconMedia?->path, $category->iconMedia?->path ? basename($category->iconMedia->path) : null),
        ];
    }

    public function recordCreated(Category $category): void
    {
        $this->record($category, OperationHistory::ACTION_CREATED, 'staff.category.create', []);
    }

    /**
     * @param array<string, array{label:string,value:mixed,display:?string}> $before
     */
    public function recordUpdated(Category $category, array $before): void
    {
        $this->record($category, OperationHistory::ACTION_UPDATED, 'staff.category.update', OperationHistoryChangeSetBuilder::fromSnapshots($before, $this->capture($category)));
    }

    /**
     * @return array{label:string,value:mixed,display:?string}
     */
    private function item(string $label, mixed $value, ?string $display): array
    {
        return compact('label', 'value', 'display');
    }

    /**
     * @param array<int, array<string, mixed>> $changes
     */
    private function record(Category $category, string $action, string $source, array $changes): void
    {
        if ($changes === [] && $action !== OperationHistory::ACTION_CREATED) {
            return;
        }

        $actor = auth()->user();

        $this->historyCreateAction->execute(
            target: $category,
            action: $action,
            actor: $actor instanceof Model ? $actor : null,
            reason: null,
            metadata: ['source' => $source],
            changes: $changes,
        );
    }
}
