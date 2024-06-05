<?php

namespace App\JsonApi\V1\Tours;

use App\Models\Tour;
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
            'meeting_point' => $this->resource->meeting_point,
            'seating' => $this->resource->seating,
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
            // @TODO
        ];
    }

}
