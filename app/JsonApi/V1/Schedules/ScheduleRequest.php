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
            'period' => ['required', 'string', Rule::in(['once', 'daily', 'weekly'])],
            'day' => 'required_unless:period,once|integer|min:1|max:7',
            'date' => 'required_if:period,once|date_format:Y-m-d',
            'time' => 'required|date_format:H:i:s',
            'state' => ValidStateRule::make(ScheduleState::class)
        ];
    }

    // public function withValidator($validator)
    // {
    //     if ($this->isCreatingOrUpdating()) {
    //         $validator->after(
    //             function ($validator) {
    //                 if (! $validator->failed()) {

    //                     dd($validator->safe());
    //                     $requestProductId = $validator->safe()->scheduleable['id'];


    //                 }
    //             }
    //         );
    //     }
    // }
}
