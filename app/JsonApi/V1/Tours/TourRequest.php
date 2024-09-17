<?php

namespace App\JsonApi\V1\Tours;

use App\States\Tour\TourState;
use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;
use Spatie\ModelStates\Validation\ValidStateRule;

class TourRequest extends ResourceRequest
{

    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:128',
            'description' => 'required|string|max:2048',
            'short_description' => 'required|string|max:256',
            'duration' => 'required|date_format:H:i:s',
            'meetingPoint' => 'required|string|max:128',
            'capacity' => 'required|integer',
            'minimum_payment_quantity' => 'required|integer',
            'bookings_impact_availability' => 'required|boolean',
            'book_without_payment' => 'required|boolean',
            'endDate' => 'required|date_format:Y-m-d',
            'image' => 'prohibited',
            'state' => ValidStateRule::make(TourState::class)
        ];
    }

}
