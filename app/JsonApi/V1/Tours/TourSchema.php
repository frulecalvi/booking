<?php

namespace App\JsonApi\V1\Tours;

use App\JsonApi\Filters\WhereEventDateFilter;
use App\Models\Tour;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Relations\HasManyThrough;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class TourSchema extends Schema
{

    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = Tour::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            ID::make()->ulid(),
            Str::make('name'),
            Str::make('description'),
            Str::make('short_description'),
            Str::make('duration'),
            Str::make('meetingPoint'),
            Number::make('capacity'),
            Number::make('minimum_payment_quantity'),
            Boolean::make('bookings_impact_availability'),
            Boolean::make('book_without_payment'),
            Str::make('endDate'),
            Str::make('image'),
            Str::make('state'),
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),
            HasMany::make('prices'),
            HasMany::make('schedules'),
            BelongsTo::make('tourCategory'),
            HasMany::make('events')->withFilters(
                WhereEventDateFilter::make('events.date', 'events.date_time')
            ),
            HasMany::make('paymentMethods'),
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
            Where::make('tourCategoryId')
            // Where::make('capacity'),
            // Where::make('events.date')
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
