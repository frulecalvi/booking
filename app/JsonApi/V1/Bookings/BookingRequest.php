<?php

namespace App\JsonApi\V1\Bookings;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class BookingRequest extends ResourceRequest
{

    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'event' => ['required', JsonApiRule::toOne()],
            'contactName' => 'required|string|max:64',
            'contactEmail' => 'required|email|max:64'
        ];
    }
}
