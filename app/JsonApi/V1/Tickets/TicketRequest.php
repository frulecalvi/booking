<?php

namespace App\JsonApi\V1\Tickets;

use App\Models\Booking;
use App\Models\Price;
use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class TicketRequest extends ResourceRequest
{

    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'person_id' => 'required',
            'nationality' => 'required',
            'quantity' => 'required',
            // 'product' => ['required', JsonApiRule::toOne()],
            'booking' => ['required', JsonApiRule::toOne()],
            'price' => ['required', JsonApiRule::toOne()],
        ];
    }

    public function withValidator($validator)
    {
        if ($this->isCreatingOrUpdating()) {
            $validator->after(
                function ($validator) {
                    if (! $validator->failed()) {
                        $requestPriceId = $validator->safe()->price['id'];
                        $requestBookingId = $validator->safe()->booking['id'];
    
                        $price = Price::findOrFail($requestPriceId);
                        $priceProduct = $price->priceable;
                        $booking = Booking::findOrFail($requestBookingId);
                        $bookingProduct = $booking->bookingable;

                        // dd([$priceProduct, $bookingProduct]);

                        // dd([$validator->safe()->quantity, $booking->event->availability()['prices'][$price->id]]);
                        
                        if ($priceProduct->id !== $bookingProduct->id) {
                            $validator->errors()->add(
                                'booking',
                                "The resource is not properly related."
                            );
                        } elseif ($validator->safe()->quantity > $booking->event->availability()['total']) {
                            $validator->errors()->add(
                                'quantity',
                                "The value of the quantity field cannot be higher than the total availability for the event."
                            );
                        } elseif ($validator->safe()->quantity > $booking->event->availability()['prices'][$price->id]) {
                            $validator->errors()->add(
                                'quantity',
                                "The value of the quantity field cannot be higher than the price availability for the event."
                            );
                        }
                    }
                }
            );
        }
    }
}
