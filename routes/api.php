<?php

use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\ScheduleController;
use App\Http\Controllers\Api\V1\TourController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use LaravelJsonApi\Laravel\Routing\Relationships;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('bookings', JsonApiController::class);
        $server->resource('tours', JsonApiController::class)->relationships(function (Relationships $relationships) {
            $relationships->hasMany('events');
        });
        $server->resource('schedules', JsonApiController::class);
        $server->resource('events', JsonApiController::class)->only('index', 'store');
        $server->resource('prices', JsonApiController::class)->only('index', 'store');
    });
