<?php

namespace App\JsonApi\V1\Bookings;

use App\Models\Booking;
use App\Models\Tour;
use App\Services\BookingService;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

/**
 * @property Booking $resource
 */
class BookingResource extends JsonApiResource
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
            'referenceCode' => $this->resource->reference_code,
//            'contactName' => $this->resource->contact_name,
            'contactEmail' => $this->resource->contact_email,
            'contactPhoneNumber' => $this->resource->contact_phone_number,
            'state' => $this->resource->state,
            'createdAt' => $this->resource->created_at,
            'updatedAt' => $this->resource->updated_at,
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
            $this->relation('event'),
            $this->relation('schedule'),
            $this->relation('product', 'bookingable'),
            $this->relation('tickets'),
        ];
    }

    public function meta($request): iterable
    {
        $bookingService = new BookingService();
        $meta = ['totalPrice' => $bookingService->calculateTotalPrice($this->resource)];

        if ($this->resource->bookingable instanceof Tour) {
            $meta['tourCategory'] = [
                'id' => $this->resource->bookingable->tourCategory->id,
                'name' => $this->resource->bookingable->tourCategory->name,
            ];
        }

        return $meta;
    }
}
