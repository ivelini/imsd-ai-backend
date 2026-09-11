<?php

namespace App\Http\Controllers\Booking;

use App\Actions\Booking\GetBookingCatalog;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

/** Каталог шага услуг: активные услуги (с флагом прайс-правил) и комплексы с составом. */
#[Group('Запись на шиномонтаж', weight: 20)]
final readonly class GetBookingCatalogController
{
    public function __construct(private GetBookingCatalog $getBookingCatalog) {}

    public function __invoke(): JsonResponse
    {
        return response()->json(['data' => $this->getBookingCatalog->execute()]);
    }
}
