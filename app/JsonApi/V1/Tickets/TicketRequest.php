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
                        
                        if ($priceProduct->id !== $bookingProduct->id) {
                            $validator->errors()->add(
                                'booking',
                                "The resource is not properly related."
                            );
                        }
                    }
                }
            );
        }
    }
}
