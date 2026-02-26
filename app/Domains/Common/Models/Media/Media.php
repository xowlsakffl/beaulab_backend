<?php

namespace App\Domains\Common\Models\Media;

use App\Domains\Common\Models\Concerns\HasAuditLogs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Media extends Model
{
    use SoftDeletes, HasAuditLogs;

    protected $table = 'media';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'model_type',
        'model_id',
        'collection',
        'disk',
        'path',
        'mime_type',
        'size',
        'width',
        'height',
        'sort_order',
        'is_primary',
        'metadata',
    ];

    protected $casts = [
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'sort_order' => 'integer',
        'is_primary' => 'boolean',
        'metadata' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Polymorphic: Hospital, Beauty, Agency 등 어떤 모델에도 붙을 수 있음
     * 사용 예
     * $media = Media::find(1);
     * $owner = $media->model; // Hospital 또는 Beauty 모델
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /* -------------------------------------------------------------------------
     | Scopes
     |------------------------------------------------------------------------- */

    /**
     * 특정 엔티티(병원 등)에 속한 미디어만 가져옴
     * Media::for($hospital)->get();
     */
    public function scopeFor(Builder $query, Model $owner): Builder
    {
        return $query
            ->where('model_type', $owner::class)
            ->where('model_id', $owner->getKey());
    }

    /**
     * 특정 엔티티(병원 등)에 속한 미디어만 가져옴
     * Media::for($hospital)->collection('logo')->get();
     */
    public function scopeCollection(Builder $query, string $collection): Builder
    {
        return $query->where('collection', $collection);
    }

    /**
     * 특정 엔티티(병원 등)에 속한 미디어만 가져옴
     * Media::for($hospital)->collection('thumbnail')->primary()->first();
     */
    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    /**
     * 내부 이미지 같은 경우 정렬 순서 적용
     * Media::for($hospital)->collection('gallery')->ordered()->get();
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /* -------------------------------------------------------------------------
     | Helpers
     |------------------------------------------------------------------------- */

    public function isImage(): bool
    {
        $mime = (string) ($this->mime_type ?? '');

        return str_starts_with($mime, 'image/');
    }

    /**
     * 대표 이미지 설정 (같은 owner+collection 내 기존 대표는 false 처리)
     * 이 미디어를 대표 이미지로 지정/해제
     *
     * @param bool $state true면 대표 지정, false면 대표 해제
     */
    public function setPrimary(bool $state = true): void
    {
        if ($state === false) {
            $this->forceFill(['is_primary' => false])->save();
            return;
        }

        // 같은 owner+collection 대표 모두 해제 후, 이 레코드만 대표로
        $others = self::query()
            ->where('model_type', $this->model_type)
            ->where('model_id', $this->model_id)
            ->where('collection', $this->collection)
            ->where('is_primary', true)
            ->whereKeyNot($this->getKey())
            ->get();

        foreach ($others as $other) {
            $other->forceFill(['is_primary' => false])->save();
        }

        $this->forceFill(['is_primary' => true])->save();
    }

    /**
     * 정렬 순서 변경
     */
    public function setSortOrder(int $order): void
    {
        $this->forceFill(['sort_order' => max(0, $order)])->save();
    }
}
