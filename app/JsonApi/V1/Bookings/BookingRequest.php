<?php

namespace App\JsonApi\V1\Bookings;

use App\Models\Event;
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
            'schedule' => ['required', JsonApiRule::toOne()],
            'product' => ['required', JsonApiRule::toOne()],
            'contactName' => 'required|string|max:64',
            'contactEmail' => 'required|email|max:64'
        ];
    }

    public function withValidator($validator)
    {
        if ($this->isCreatingOrUpdating()) {
            $validator->after(
                function ($validator) {
                    if (!$validator->failed()) {

                        $requestEventId = $validator->safe()->event['id'];
                        $requestScheduleId = $validator->safe()->schedule['id'];
                        $requestProductId = $validator->safe()->product['id'];
    
                        $event = Event::findOrFail($requestEventId);
                        $eventSchedule = $event->schedule;
                        $eventScheduleProduct = $eventSchedule->scheduleable;
    
                        if ($eventSchedule->id !== $requestScheduleId) {
                            $validator->errors()->add(
                                'schedule',
                                "The resource is not properly related."
                            );
                        }
                        
                        if ($eventScheduleProduct->id !== $requestProductId) {
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
