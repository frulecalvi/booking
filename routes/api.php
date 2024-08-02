<?php

use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\PaymentMethodController;
use App\Http\Controllers\MercadoPagoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use LaravelJsonApi\Laravel\Routing\Relationships;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use LaravelJsonApi\Laravel\Routing\ActionRegistrar;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('bookings', BookingController::class)
            ->actions('-actions', function (ActionRegistrar $actions) {
                $actions->withId()->post('calculate-total-price');
                $actions->withId()->post('mp-create-preference');
            });
        $server->resource('tours', JsonApiController::class)->relationships(function (Relationships $relationships) {
            $relationships->hasMany('prices');
            $relationships->hasMany('schedules');
            $relationships->hasMany('events');
        });
        $server->resource('schedules', JsonApiController::class);
        $server->resource('events', JsonApiController::class)->only('index', 'show', 'store');
        $server->resource('prices', JsonApiController::class)->only('index', 'store', 'destroy');
        $server->resource('tickets', JsonApiController::class)->only('index', 'store');
        $server->resource('tour-categories', JsonApiController::class)->only('index', 'show');
        $server->resource('payments', JsonApiController::class)->only('store', 'index');
        $server->resource('payment-methods', PaymentMethodController::class)
            ->only('index', 'show', 'store')
            ->actions('-actions', function (ActionRegistrar $actions) {
                $actions->withId()->post('prepare-payment');
            });
    });

Route::post('mercadopago-webhook', [MercadoPagoController::class, 'webhook'])->name('mercadopagoWebhook');