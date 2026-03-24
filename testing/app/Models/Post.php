<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    public const CREATED_AT = 'post_date';
    public const UPDATED_AT = 'post_modified';

    protected $table = 'posts';
    protected $primaryKey = 'ID';

    protected $fillable = [
        'post_author',
        'post_date',
        'post_date_gmt',
        'post_content',
        'post_title',
        'post_excerpt',
        'post_status',
        'comment_status',
        'ping_status',
        'post_password',
        'post_name',
        'to_ping',
        'pinged',
        'post_modified',
        'post_modified_gmt',
        'post_content_filtered',
        'post_parent',
        'guid',
        'menu_order',
        'post_type',
        'post_mime_type',
        'comment_count',
    ];

    protected $casts = [
        'post_author' => 'integer',
        'post_date' => 'datetime',
        'post_date_gmt' => 'datetime',
        'post_modified' => 'datetime',
        'post_modified_gmt' => 'datetime',
        'post_parent' => 'integer',
        'menu_order' => 'integer',
        'comment_count' => 'integer',
    ];

    protected static function booted()
    {
        static::addGlobalScope('post_type', function ($builder) {
            $builder->where('post_type', 'post');
        });
    }

    public function scopePublished($query)
    {
        return $query->where('post_status', 'publish');
    }

    public function scopeRecent($query, int $count = 5)
    {
        return $query->published()
            ->orderBy('post_date', 'desc')
            ->limit($count);
    }

    public function meta(): HasMany
    {
        return $this->hasMany(PostMeta::class, 'post_id', 'ID');
    }

    public function getMeta(string $key, $default = null)
    {
        $meta = $this->meta()->where('meta_key', $key)->first();
        return $meta ? $meta->meta_value : $default;
    }

    public function title(): string
    {
        return $this->post_title ?: '';
    }

    public function content(): string
    {
        return apply_filters('the_content', $this->post_content);
    }

    public function excerpt(int $length = 55): string
    {
        if ($this->post_excerpt) {
            return $this->post_excerpt;
        }

        return wp_trim_words($this->content(), $length);
    }

    public function permalink(): string
    {
        return get_permalink($this->ID) ?: '';
    }

    public function thumbnail(string $size = 'thumbnail'): ?string
    {
        return get_the_post_thumbnail_url($this->ID, $size) ?: null;
    }

    public function hasThumbnail(): bool
    {
        return has_post_thumbnail($this->ID);
    }

    public function categories(): array
    {
        $terms = get_the_terms($this->ID, 'category');
        return is_array($terms) ? $terms : [];
    }

    public function tags(): array
    {
        $terms = get_the_terms($this->ID, 'post_tag');
        return is_array($terms) ? $terms : [];
    }
}
