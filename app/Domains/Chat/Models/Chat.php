<?php

namespace App\Domains\Chat\Models;

use App\Domains\AccountUser\Models\AccountUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 1:1 채팅방 헤더 모델.
 * match_key로 사용자 쌍당 채팅방 1개 정책을 유지하고 마지막 메시지를 역정규화한다.
 */
final class Chat extends Model
{
    use SoftDeletes;

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_SUSPENDED = 'SUSPENDED';
    public const STATUS_CLOSED = 'CLOSED';

    protected $table = 'chats';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'status',
        'match_key',
        'created_by_user_id',
        'last_message_id',
        'last_message_at',
        'metadata',
        'closed_at',
    ];

    protected $casts = [
        'created_by_user_id' => 'integer',
        'last_message_id' => 'integer',
        'last_message_at' => 'datetime',
        'metadata' => 'array',
        'closed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(AccountUser::class, 'created_by_user_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ChatParticipant::class, 'chat_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'chat_id')
            ->orderBy('id');
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'last_message_id');
    }
}
