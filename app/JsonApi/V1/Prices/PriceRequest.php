<?php

namespace App\JsonApi\V1\Prices;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class PriceRequest extends ResourceRequest
{

    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'title' => 'required',
            'description' => 'required',
            'amount' => 'required',
            'currency' => 'required',
            'product' => [JsonApiRule::toOne()]
        ];
    }

}
