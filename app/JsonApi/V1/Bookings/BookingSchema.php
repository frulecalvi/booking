<?php

namespace App\JsonApi\V1\Bookings;

use App\Models\Booking;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasOneThrough;
use LaravelJsonApi\Eloquent\Fields\Relations\MorphTo;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class BookingSchema extends Schema
{

    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = Booking::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            ID::make()->ulid(),
            Str::make('referenceCode'),
//            Str::make('contactName'),
            Str::make('contactEmail'),
            // DateTime::make('createdAt')->sortable()->readOnly(),
            // DateTime::make('updatedAt')->sortable()->readOnly(),
            // DateTime::make('deletedAt')->sortable()->readOnly(),
            BelongsTo::make('event'),
            BelongsTo::make('schedule'),
            MorphTo::make('product', 'bookingable')->types('tours', 'shows'),
            Str::make('state'),
        ];
    }

    /**
     * Get the resource filters.
     *
     * @return array
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
        ];
    }

    /**
     * Get the resource paginator.
     *
     * @return Paginator|null
     */
    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

}
