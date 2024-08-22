<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\MercadoPago;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Error;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Core\Responses\ErrorResponse;
use LaravelJsonApi\Core\Responses\MetaResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;

class PaymentMethodController extends Controller
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

    public function preparePayment(Request $request, MercadoPago $mercadoPago)
    {
        $paymentMethod = $request->route('payment_method');
        $booking = Booking::findOrFail($request->query('bookingId'));

//        dd($booking->bookingable->minimum_payment_quantity, count($booking->tickets));

        if (count($booking->tickets) < $booking->bookingable->minimum_payment_quantity) {
            $error = Error::fromArray([
                'status' => 422,
                'detail' => "The referenced booking does not reach the minimum required quantity to make a payment.",
            ]);

            return ErrorResponse::make($error);
        }

        if ($paymentMethod->payment_method_type === 'mercadopago') {
            $mercadoPago->setConfig($paymentMethod->secrets['access_token']);

            try {
                $preferenceId = $mercadoPago->createPreference($paymentMethod, $booking);
            } catch (\Exception $exception) {
                $error = Error::fromArray([
                    'status' => 500,
                    'detail' => $exception->getMessage(),
                ]);

                return ErrorResponse::make($error);
            }

            $metaResponse = ['preferenceId' => $preferenceId];
        }

        if (! isset($metaResponse)) {
            $error = Error::fromArray([
                'status' => 500,
                'detail' => 'Payment method type not found',
            ]);

            return ErrorResponse::make($error);
        }

        return MetaResponse::make($metaResponse);
    }
}
