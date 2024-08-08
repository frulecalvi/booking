<?php

namespace App\JsonApi\V1\PaymentMethods;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class PaymentMethodRequest extends ResourceRequest
{

    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:64',
            'paymentMethodType' => 'required|string|max:64',
            'secrets' => 'array',
        ];
    }

}
