<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Models;

use App\Common\Concerns\HasAuditLogs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Q&A 폴더. 2단 고정이다(대분류 > 소분류 > 질문).
 *
 * 무제한 트리로 두면 SDK 탐색 UI와 이동 UX가 복잡해지는 데 비해 얻는 게 적다.
 * 깊이를 늘려야 하면 MAX_DEPTH와 검증만 바꾸면 된다.
 */
final class HospitalChatBotQnaFolder extends Model
{
    use HasAuditLogs;

    public const MAX_DEPTH = 2;

    protected $table = 'hospital_chatbot_qna_folders';

    protected $fillable = [
        'parent_id',
        'depth',
        'name',
        'sort_order',
        'created_by_staff_id',
        'updated_by_staff_id',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'depth' => 'integer',
        'sort_order' => 'integer',
        'created_by_staff_id' => 'integer',
        'updated_by_staff_id' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    public function qnas(): HasMany
    {
        return $this->hasMany(HospitalChatBotQna::class, 'folder_id');
    }
}
