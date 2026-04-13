<?php

namespace App\Domains\Chat\Models;

use App\Domains\AccountUser\Models\AccountUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ChatParticipant extends Model
{
    protected $table = 'chat_participants';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'chat_id',
        'account_user_id',
        'last_read_message_id',
        'last_read_at',
        'notifications_enabled',
    ];

    protected $casts = [
        'chat_id' => 'integer',
        'account_user_id' => 'integer',
        'last_read_message_id' => 'integer',
        'last_read_at' => 'datetime',
        'notifications_enabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'notifications_enabled' => true,
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class, 'chat_id');
    }

    public function accountUser(): BelongsTo
    {
        return $this->belongsTo(AccountUser::class, 'account_user_id');
    }

    public function lastReadMessage(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'last_read_message_id');
    }
}
