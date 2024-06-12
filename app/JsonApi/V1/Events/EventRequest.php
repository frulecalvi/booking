<?php

namespace App\JsonApi\V1\Events;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class EventRequest extends ResourceRequest
{

    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'product' => ['required', JsonApiRule::toOne()],
            'dateTime' => 'required_if:period,once|date_format:Y-m-d H:i:s',
        ];
    }

}
