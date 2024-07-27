<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\BookingService;
use App\Services\MercadoPago;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Responses\ErrorResponse;
use LaravelJsonApi\Core\Responses\MetaResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;

class BookingController extends Controller
{

    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;
    use Actions\Destroy;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;
    use Actions\UpdateRelationship;
    use Actions\AttachRelationship;
    use Actions\DetachRelationship;

    private $bookingService;

    public function __construct(
        BookingService $bookingService
    )
    {
        $this->bookingService = new BookingService();
    }

    public function calculateTotalPrice()
    {
        $booking = request()->route('booking');

        $totalPrice = $this->bookingService->calculateTotalPrice($booking);

        return MetaResponse::make(['totalPrice' => formatPriceAsString($totalPrice)]);
    }

    public function mpCreatePreference(Request $request, MercadoPago $mercadoPago)
    {
        $booking = request()->route('booking');

//        dd($payment);

        try {
            $preferenceId = $mercadoPago->createPreferenceForBooking($booking);
        } catch (\Exception $exception) {
            $error = [
                'status' => 500,
                'detail' => $exception->getMessage(),
            ];

            return ErrorResponse::make($error);
        }

        return MetaResponse::make(['preferenceId' => $preferenceId]);
    }
}
