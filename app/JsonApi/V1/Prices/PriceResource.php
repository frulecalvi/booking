<?php

namespace App\JsonApi\V1\Prices;

use App\Models\Price;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

/**
 * @property Price $resource
 */
class PriceResource extends JsonApiResource
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
            'title' => $this->resource->title,
            'description' => $this->resource->description,
            'amount' => $this->resource->amount,
            'currency' => $this->resource->currency,
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
            $this->relation('product', 'priceable')
        ];
    }

}
