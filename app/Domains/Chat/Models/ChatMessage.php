<?php

namespace App\Domains\Chat\Models;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Models\Media\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 채팅 메시지 모델.
 * 현재 API는 TEXT만 허용하지만, Media 연동을 위해 IMAGE/FILE 타입 확장 포인트를 남겨둔다.
 */
final class ChatMessage extends Model
{
    use SoftDeletes;

    public const TYPE_TEXT = 'TEXT';

    public const TYPE_IMAGE = 'IMAGE';

    public const TYPE_FILE = 'FILE';

    public const MEDIA_COLLECTION_ATTACHMENTS = 'attachments';

    protected $table = 'chat_messages';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'chat_id',
        'sender_user_id',
        'client_message_id',
        'message_type',
        'body',
        'reply_to_message_id',
        'metadata',
        'edited_at',
    ];

    protected $casts = [
        'chat_id' => 'integer',
        'sender_user_id' => 'integer',
        'reply_to_message_id' => 'integer',
        'metadata' => 'array',
        'edited_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'message_type' => self::TYPE_TEXT,
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class, 'chat_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(AccountUser::class, 'sender_user_id');
    }

    public function replyToMessage(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_message_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')
            ->where('collection', self::MEDIA_COLLECTION_ATTACHMENTS)
            ->ordered();
    }
}
