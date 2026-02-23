<?php

declare(strict_types=1);

namespace App\Domains\Common\Models\BusinessRegistration;

use App\Domains\Common\Models\Media\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BusinessRegistration extends Model
{
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
        'certificate_media_id',
        'issued_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
        ];
    }

    public function certificateMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'certificate_media_id');
    }
}
