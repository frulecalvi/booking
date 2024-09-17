<?php

namespace App\JsonApi\V1\Tours;

use App\Models\Tour;
use Carbon\Carbon;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

/**
 * @property Tour $resource
 */
class TourResource extends JsonApiResource
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
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'short_description' => $this->resource->short_description,
            'duration' => $this->resource->duration,
            'meetingPoint' => $this->resource->meeting_point,
            'capacity' => $this->resource->capacity,
            'minimum_payment_quantity' => $this->resource->minimum_payment_quantity,
            'bookings_impact_availability' => $this->resource->bookings_impact_availability,
            'book_without_payment' => $this->resource->book_without_payment,
            'endDate' => $this->resource->end_date,
            'image' => $this->resource->image,
            'state' => $this->resource->state,
//            'createdAt' => $this->resource->created_at,
//            'updatedAt' => $this->resource->updated_at,
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
            $this->relation('prices'),
            $this->relation('schedules'),
            $this->relation('events'),
            $this->relation('tourCategory'),
            $this->relation('paymentMethods'),
        ];
    }

    public function meta($request): iterable
    {
        return ['availableDates' => $this->availableDates()];
    }
}
