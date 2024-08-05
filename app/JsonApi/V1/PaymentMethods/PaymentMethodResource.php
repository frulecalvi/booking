<?php

namespace App\JsonApi\V1\PaymentMethods;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

/**
 * @property PaymentMethod $resource
 */
class PaymentMethodResource extends JsonApiResource
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
            'payment_method_type' => $this->resource->payment_method_type,
            'secrets' => $this->when($request->user()?->hasRole('Admin'), $this->resource->secrets),
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
