<?php

namespace App\Blocks;

class LatestSeeds
{
    /**
     * Render callback for register_block_type
     */
    public function render(array $attributes, string $content): string
    {
        $posts = $attributes['posts'] ?? 5;
        $displayPostContent = $attributes['displayPostContent'] ?? 'none';
        $postLayout = $attributes['postLayout'] ?? 'list';
        $displayFeaturedImage = $attributes['displayFeaturedImage'] ?? false;

        $query_args = [
            'post_type' => 'seed',
            'numberposts' => $posts,
            'post_status' => 'publish',
            'order' => 'DESC',
            'orderby' => 'date',
        ];

        $seeds = get_posts($query_args);

        return view('blocks.latest-seeds', [
            'seeds' => $seeds,
            'posts' => $posts,
            'displayPostContent' => $displayPostContent,
            'postLayout' => $postLayout,
            'displayFeaturedImage' => $displayFeaturedImage,
        ]);
    }
}
