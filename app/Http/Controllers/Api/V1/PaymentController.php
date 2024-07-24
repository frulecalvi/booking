<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\JsonApi\V1\Payments\PaymentRequest;
use App\Services\MercadoPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use LaravelJsonApi\Core\Responses\ErrorResponse;
use LaravelJsonApi\Core\Responses\MetaResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

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

    public function mpUpdate(Request $request, MercadoPago $mercadoPago)
    {
        $webhookSecret = config('mercadopago.webhook_secret');
        $xRequestId = $request->header('x-request-id');
        $xSignature = $request->header('x-signature');
        $dataId = $request->query('data_id');

        try {
            $webhookValidation = $mercadoPago->validateWebhookNotification(
                webhookSecret: $webhookSecret,
                xRequestId: $xRequestId,
                xSignature: $xSignature,
                dataId: $dataId,
            );
        } catch (BadRequestException $e) {
            abort(400, $e->getMessage());
        }



        response();
    }
}
