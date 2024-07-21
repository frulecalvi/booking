<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\JsonApi\V1\Payments\PaymentRequest;
use App\Services\MercadoPago;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Responses\ErrorResponse;
use LaravelJsonApi\Core\Responses\MetaResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;

class PaymentController extends Controller
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

    public function generateMpPreference(Request $request, MercadoPago $mercadoPago)
    {
        $paymentId = request()->route('payment')->id;

        try {
            $preferenceId = $mercadoPago->createPreferenceForPayment($paymentId);
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
