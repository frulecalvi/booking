<?php

namespace App\JsonApi\V1\Events;

use App\Models\Event;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

/**
 * @property Event $resource
 */
class EventResource extends JsonApiResource
{

    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     * @return iterable
     */
    public function attributes($request): iterable
    {
        return [
            'dateTime' => $this->resource->date_time,
        ];
    }

    /**
     * Get the resource's relationships.
     *
     * @param Request|null $request
     * @return iterable
     */
    public function relationships($request): iterable
    {
        return [
            $this->relation('schedule'),
            $this->relation('product', 'eventable'),
        ];
    }

    public function meta($request): iterable
    {
        $productPrices = $this->eventable->prices;

        // dd($productPrices);
        $pricesAvailability = [];

        foreach ($productPrices as $price) {
            if ($price->capacity === 0)
                $pricesAvailability[$price->id] = 0;
            
            $priceTickets = $this->tickets->where('price_id', '=', $price->id);

            $booked = 0;
            foreach ($priceTickets as $currentTicket) {
                $booked += $currentTicket->quantity;
            }

            $pricesAvailability[$price->id] = $price->capacity - $booked;
        }

        return ['pricesAvailability' => $pricesAvailability];
    }

}
