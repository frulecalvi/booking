<?php

namespace App\JsonApi\V1\Schedules;

use App\Models\Schedule;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

/**
 * @property Schedule $resource
 */
class ScheduleResource extends JsonApiResource
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
            'day' => $this->resource->day,
            // 'date' => $this->resource->date,
            'time' => $this->resource->toArray()['time'],
            'date' => $this->resource->toArray()['date'],
            'period' => $this->resource->period,
            'state' => $this->resource->state,
            'createdAt' => $this->resource->created_at,
            'updatedAt' => $this->resource->updated_at,
            'updatedAt' => $this->resource->deleted_at
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
            $this->relation('product', 'scheduleable')
        ];
    }

}
