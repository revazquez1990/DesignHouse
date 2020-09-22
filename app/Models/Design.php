<?php

namespace App\Models;

use App\Models\Traits\Likeable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Cviebrock\EloquentTaggable\Taggable;

class Design extends Model
{
    use Taggable, Likeable;
    
    protected $fillable=[
        'user_id',
        'image',
        'title',
        'description',
        'slug',
        'close_to_comment',
        'is_live',
        'upload_successful',
        'disk'
    ];
     
    public function user() 
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable')
                    ->orderBy('created_at', 'asc');
    }

    public function getImagesAttribute()
    {
        return [
            'thumbnail' => $this->getImagesPath('thumbnail'),
            'large' => $this->getImagesPath('large'),
            'original' => $this->getImagesPath('original')
        ];
    }

    protected function getImagesPath($size)
    {
        $thumbnail = Storage::disk($this->disk)
                            ->url("uploads/designs/{$size}/". $this->image);
    }
}
