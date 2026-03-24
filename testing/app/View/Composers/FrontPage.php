<?php

namespace App\View\Composers;

use App\Models\Post;
use App\Models\Seed;
use Roots\Acorn\View\Composer;

class FrontPage extends Composer
{
    protected static $views = ['front-page'];

    public function with()
    {
        return [
            'featuredPost' => $this->getFeaturedPost(),
            'recentPosts' => $this->getRecentPosts(),
            'featuredSeeds' => $this->getFeaturedSeeds(),
            'totalPosts' => $this->getTotalPosts(),
            'totalSeeds' => $this->getTotalSeeds(),
            'totalCategories' => $this->getTotalCategories(),
        ];
    }

    protected function getFeaturedPost()
    {
        // Try to get a post marked as featured first
        $featured = Post::published()
            ->whereHas('meta', function ($query) {
                $query->where('meta_key', 'featured')
                      ->where('meta_value', '1');
            })
            ->orderBy('post_date', 'desc')
            ->first();

        // Fallback to most recent post if no featured post
        return $featured ?: Post::published()->orderBy('post_date', 'desc')->first();
    }

    protected function getRecentPosts()
    {
        $featuredId = $this->getFeaturedPost()?->ID;

        $query = Post::published()->orderBy('post_date', 'desc')->limit(6);

        // Exclude the featured post from recent posts
        if ($featuredId) {
            $query->where('ID', '!=', $featuredId);
        }

        return $query->get();
    }

    protected function getFeaturedSeeds()
    {
        // Try to get seeds marked as featured
        $featured = Seed::published()
            ->whereHas('meta', function ($query) {
                $query->where('meta_key', 'featured')
                      ->where('meta_value', '1');
            })
            ->orderBy('post_date', 'desc')
            ->limit(4)
            ->get();

        // If no featured seeds, get recent ones
        if ($featured->isEmpty()) {
            return Seed::published()
                ->orderBy('post_date', 'desc')
                ->limit(4)
                ->get();
        }

        return $featured;
    }

    protected function getTotalPosts()
    {
        return Post::published()->count();
    }

    protected function getTotalSeeds()
    {
        return Seed::published()->count();
    }

    protected function getTotalCategories()
    {
        return wp_count_terms([
            'taxonomy' => ['category', 'seed_category'],
            'hide_empty' => true,
        ]);
    }
}
