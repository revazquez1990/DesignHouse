<?php

namespace App\Http\Controllers;

use App\Http\Resources\DesignResource;
use App\Jobs\UploadImage;
use App\Models\Design;
use App\Repositories\Contracts\IDesign;
use App\Repositories\Eloquent\Criteria\{
    EagerLoad,
    ForUser,
    IsLive,
    LatestFirst
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class DesignController extends Controller
{
    protected $designs;

    public function __construct(IDesign $designs)
    {
        $this->designs = $designs;
    }

    public function index()
    {
        $design = $this->designs->withCriteria([
            new LatestFirst(),
            new IsLive(),
            new ForUser(1),
            new EagerLoad(['user', 'comments'])
        ])->all();
        return DesignResource::collection($design);
    }

    public function findDesign($id)
    {
        $design = $this->designs->find($id);
        return new DesignResource($design);
    }

    public function upload(Request $request)
    {
        // validate de request
        // $this->validate($request, [
        //     'image' => ['required', 'mimes:gif,bmp,png', 'max:2048']
        // ]);

        // get the image
        $image = $request->file('image');
        $image_path = $image->getPathname();

        //get the original file name and replace any spaces with _
        // Business card.png = timestamp()_business_card.png 
        $filename = time()."_". preg_replace('/\s+/', '_', strtolower($image->getClientOriginalName()));

        // move to image to the temporary location (tmp) 
        $tmp = $image->storeAs('uploads/original', $filename, 'tmp');

        // creating the database record for the design
        $design = auth()->user()->designs()->create([
            'image' => $filename,
            'disk' => config('site.upload_disk')
        ]);

        // dispatch a job to handle the image manipulation
        $this->dispatch(new UploadImage($design));

        return response()->json($design, 200);
    }
    
    public function update(Request $request, $id)
    {
        $design = $this->designs->find($id);
        $this->authorize('update', $design);

        // validate de request
        $this->validate($request, [
            'title' => ['required', 'unique:designs,title,'. $id],
            'description' => ['required', 'string', 'min:20', 'max:140'],
            'tags' => ['required']
        ]);

        $design = $this->designs->update($id, [
            'title' => $request->title,
            'description' => $request->description,
            'slug' => Str::slug($request->title),
            'is_live' => ! $request->upload_successful ? false : $request->is_live
        ]);

        // apply the tags
        $this->designs->applyTags($id, $request->tags);

        $design = $this->designs->find($id);

        return new DesignResource($design);
    }

    public function destroy($id)
    {
        $design = $this->designs->find($id);
        $this->authorize('delete', $design);

        // delete the files associated to the record
        foreach(['thumbnail', 'large', 'original'] as $size){
            if (Storage::disk($design->disk)->exists("upploads/designs/{$size}". $design->image)) {
                Storage::disk($design->disk)->delete("upploads/designs/{$size}". $design->image);
            }
        }

        $this->designs->delete($id);

        return response()->json(["message" => 'Record delete !!!'], 200);
    }

    public function like($id)
    {
        $this->designs->like($id);

        return response()->json([
            'message' => 'Like Successful'
        ], 200);
    }

    public function checkIfUserHasLiked($designId)
    {
        $isLiked = $this->designs->isLikedByUser($designId);
        return response()->json(['liked' => $isLiked], 200);
    }

}
