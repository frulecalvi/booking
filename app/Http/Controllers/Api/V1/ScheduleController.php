<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;

class ScheduleController extends Controller
{

    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;
    use Actions\Destroy;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;
    use Actions\UpdateRelationship;
    use Actions\AttachRelationship;
    use Actions\DetachRelationship;

    public function index(Request $request)
    {
        if (! $request->user())
            abort(401);

        if ($request->user() && $request->user()->hasRole('Admin')) {
            $schedules = Schedule::get();
        } else {
            $schedules = Schedule::active()->get();
        }

        return new DataResponse($schedules);
    }

}
