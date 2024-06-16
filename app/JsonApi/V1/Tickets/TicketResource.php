<?php

namespace App\JsonApi\V1\Tickets;

use App\Models\Ticket;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

/**
 * @property Ticket $resource
 */
class TicketResource extends JsonApiResource
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
            'personId' => $this->resource->person_id,
            'nationality' => $this->resource->nationality,
            'quantity' => $this->resource->quantity,
            // 'createdAt' => $this->resource->created_at,
            // 'updatedAt' => $this->resource->updated_at,
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
            // $this->relation('product', 'ticketable'),
            $this->relation('booking'),
            $this->relation('price'),
        ];
    }

}
