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
            'duration' => $this->resource->duration,
            'meetingPoint' => $this->resource->meeting_point,
            'capacity' => $this->resource->capacity,
            'endDate' => $this->resource->end_date,
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
            $this->relation('events'),
        ];
    }

    public function meta($request): iterable
    {
        return ['availableDates' => $this->availableDates()];
    }
}
