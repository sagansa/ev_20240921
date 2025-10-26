<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class YouTubeCollection extends Model
{
    use HasUuids;
    use HasFactory;
    use SoftDeletes;

     protected $connection = 'ev'; // Use the sagansa database connection

    protected $table = 'youtube_collections';

    protected $fillable = [
        'title',
        'video_id',
        'description',
        'thumbnail_url',
        'channel_name',
        'category',
        'view_count',
        'published_at',
        'is_active',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_active' => 'boolean',
        'view_count' => 'integer',
    ];
}
