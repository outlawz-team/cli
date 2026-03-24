<?php

namespace App\Models;

class Seed extends Post
{
    protected static function booted()
    {
        static::addGlobalScope('post_type', function ($builder) {
            $builder->where('post_type', 'seed');
        });
    }

    public function categories(): array
    {
        $terms = get_the_terms($this->ID, 'seed_category');
        return is_array($terms) ? $terms : [];
    }

    public function nextSeed(): ?static
    {
        return static::published()
            ->where('post_date', '>', $this->post_date)
            ->orderBy('post_date', 'asc')
            ->first();
    }

    public function previousSeed(): ?static
    {
        return static::published()
            ->where('post_date', '<', $this->post_date)
            ->orderBy('post_date', 'desc')
            ->first();
    }
}
