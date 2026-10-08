<?php

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Domains\HospitalEvent\Dto\Staff\HospitalEventForStaffDetailDto;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use Illuminate\Support\Facades\Gate;

final class HospitalEventGetForStaffAction
{
    public function execute(HospitalEvent $event): array
    {
        Gate::authorize('view', $event);

        [$expression, $bindings] = app(\App\Domains\ConsultationPricing\Queries\HospitalEventPricingQuery::class)->expression();
        $price = HospitalEvent::whereKey($event->id)->selectRaw('('.$expression.') as resolved_price', $bindings)->firstOrFail()->resolved_price;
        $event->setAttribute('consultation_price', (int) $price);
        $event->setAttribute('base_consultation_price', (int) $price);

        return [
            'event' => HospitalEventForStaffDetailDto::fromModel($event->load([
                'hospital.businessRegistration',
                'categories',
                'doctors',
                'options',
                'thumbnailImage',
                'eventPageImage',
                'beforeAfterPhotos',
            ]))->toArray(),
        ];
    }
}
