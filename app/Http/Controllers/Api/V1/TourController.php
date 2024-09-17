<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;

class TourController extends Controller
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

    public function uploadImage(Request $request) {
        $tour = $request->route('tour');

//        dd($request->file('image'));

        $timestamp = now()->timestamp;
        $imageExtension = $request->file('image')->extension();

        $fileName = "tour_images/{$tour->id}_{$timestamp}.{$imageExtension}";

        $uploadedImagePath = basename($request->file('image')->storeAs('public', $fileName));

        $tour->image = $uploadedImagePath;

//        dd(Storage::url($uploadedImagePath));
        $tour->save();

        return DataResponse::make($tour);
    }
}
