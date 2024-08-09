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

        if ($paymentMethod->payment_method_type === 'mercadopago') {
            $mercadoPago->setConfig($paymentMethod->secrets['access_token']);

            $booking = Booking::findOrFail($request->query('bookingId'));

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

        $metaResponse = $metaResponse ?? ['Payment method type not found'];

        return MetaResponse::make($metaResponse);
    }
}
