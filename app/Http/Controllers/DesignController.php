<?php

namespace App\Http\Controllers;

use App\Http\Resources\DesignResource;
use App\Jobs\UploadImage;
use App\Models\Design;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class DesignController extends Controller
{
    public function upload(Request $request)
    {
        // validate de request
        $this->validate($request, [
            'image' => ['required', 'mimes:jpeg,gif,bmp,png', 'max:2048']
        ]);

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
        $design = Design::findOrFail($id);
        $this->authorize('update', $design);

        // validate de request
        $this->validate($request, [
            'title' => ['required', 'unique:designs,title,'. $id],
            'description' => ['required', 'string', 'min:20', 'max:140'],
            'tags' => ['required']
        ]);

        $design->update([
            'title' => $request->title,
            'description' => $request->description,
            'slug' => Str::slug($request->title),
            'is_live' => ! $request->upload_successful ? false : $request->is_live
        ]);

        // apply the tags
        $design->retag($request->tags);

        return new DesignResource($design);
    }

    public function destroy($id)
    {
        $design = Design::findOrFail($id);
        $this->authorize('delete', $design);

        // delete the files associated to the record
        foreach(['thumbnail', 'large', 'original'] as $size){
            if (Storage::disk($design->disk)->exists("upploads/designs/{$size}". $design->image)) {
                Storage::disk($design->disk)->delete("upploads/designs/{$size}". $design->image);
            }
        }

        $design->delete();

        return response()->json(["message" => 'Record delete !!!'], 200);
    }

}
