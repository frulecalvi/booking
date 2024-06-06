<?php

namespace App\JsonApi\V1\Schedules;

use App\States\Schedule\ScheduleState;
use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;
use Spatie\ModelStates\Validation\ValidStateRule;

class ScheduleRequest extends ResourceRequest
{

    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'product' => ['required', JsonApiRule::toOne()],
            'period' => ['required', 'string', Rule::in(['once', 'daily', 'weekly', 'monthly'])],
            'day' => 'required_unless:period,once|integer|min:1|max:7',
            'time' => 'required|date_format:H:i:s',
            'endDate' => 'required|date_format:Y-m-d',
            'state' => ValidStateRule::make(ScheduleState::class)
        ];
    }

}
