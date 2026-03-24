<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostMeta extends Model
{
    public $timestamps = false;

    protected $table = 'postmeta';
    protected $primaryKey = 'meta_id';

    protected $fillable = [
        'post_id',
        'meta_key',
        'meta_value',
    ];

    protected $casts = [
        'post_id' => 'integer',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id', 'ID');
    }
}
