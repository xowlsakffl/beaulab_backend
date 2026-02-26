<?php

declare(strict_types=1);

namespace App\Domains\Common\Models\BusinessRegistration;

use App\Domains\Common\Models\Concerns\HasAuditLogs;
use App\Domains\Common\Models\Media\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class BusinessRegistration extends Model
{
    use HasAuditLogs;

    // status
    public const STATUS_ACTIVE    = 'ACTIVE';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_REVOKED = 'REVOKED';

    protected $table = 'business_registrations';

    protected $fillable = [
        'owner_type',
        'owner_id',
        'business_number',
        'company_name',
        'ceo_name',
        'business_type',
        'business_item',
        'business_address',
        'business_address_detail',
        'issued_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
        ];
    }

    public function certificateMedia(): HasOne
    {
        return $this->hasOne(Media::class, 'model_id', 'id')
            ->where('model_type', self::class)
            ->where('collection', 'business_registration_file');
    }
}
