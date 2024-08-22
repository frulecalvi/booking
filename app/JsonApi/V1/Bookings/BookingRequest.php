<?php

namespace App\JsonApi\V1\Bookings;

use App\Models\Event;
use App\States\Booking\BookingState;
use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;
use Spatie\ModelStates\Validation\ValidStateRule;

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
            'schedule' => JsonApiRule::toOne(),
            'product' => ['required', JsonApiRule::toOne()],
//            'contactName' => 'required|string|max:64',
            'contactEmail' => 'required|email|max:64',
            'contactPhoneNumber' => 'required|string|max:64',
            'state' => ValidStateRule::make(BookingState::class)
        ];
    }

    public function withValidator($validator)
    {
        if ($this->isCreatingOrUpdating()) {
            $validator->after(
                function ($validator) {
                    if (! $validator->failed()) {
                        $requestEventId = $validator->safe()->event['id'];
                        $requestProductId = $validator->safe()->product['id'];
    
                        $event = Event::findOrFail($requestEventId);
                        $eventProduct = $event->eventable;
                        
                        if ($eventProduct->id !== $requestProductId) {
                            $validator->errors()->add(
                                'product',
                                "The resource is not properly related."
                            );
                        }
                    }
                }
            );
        }
    }
}
