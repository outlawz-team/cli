<?php

namespace App\Http\Controllers\Api;

use App\Models\Seed;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class SeedController
{
    public function index(WP_REST_Request $request): WP_REST_Response
    {
        $page = (int) ($request->get_param('page') ?? 1);
        $perPage = min((int) ($request->get_param('per_page') ?? 10), 100);
        $search = $request->get_param('search');

        $query = Seed::published()->orderBy('post_date', 'desc');

        if ($search) {
            $query->where('post_title', 'like', "%{$search}%");
        }

        $seeds = $query->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $total = Seed::published()->count();

        $data = $seeds->map(function ($seed) {
            return [
                'id' => $seed->ID,
                'title' => $seed->title(),
                'excerpt' => $seed->excerpt(30),
                'permalink' => $seed->permalink(),
                'thumbnail' => $seed->thumbnail('medium'),
                'date' => $seed->post_date->format('Y-m-d H:i:s'),
                'categories' => array_map(fn ($cat) => [
                    'id' => $cat->term_id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                ], $seed->categories()),
            ];
        })->toArray();

        $response = new WP_REST_Response($data);
        $response->header('X-WP-Total', $total);
        $response->header('X-WP-TotalPages', ceil($total / $perPage));

        return $response;
    }

    public function show(WP_REST_Request $request, ?int $id = null): WP_REST_Response|WP_Error
    {
        $id = $id ?? (int) $request->get_param('id');

        if (!$id) {
            return new WP_Error('missing_id', 'Seed ID is required', ['status' => 400]);
        }

        $seed = Seed::find($id);

        if (!$seed) {
            return new WP_Error('seed_not_found', 'Seed not found', ['status' => 404]);
        }

        $data = [
            'id' => $seed->ID,
            'title' => $seed->title(),
            'content' => $seed->content(),
            'excerpt' => $seed->excerpt(),
            'permalink' => $seed->permalink(),
            'thumbnail' => $seed->thumbnail('large'),
            'date' => $seed->post_date->format('Y-m-d H:i:s'),
            'modified' => $seed->post_modified->format('Y-m-d H:i:s'),
            'author' => [
                'id' => $seed->post_author,
                'name' => get_user_by('ID', $seed->post_author)?->display_name ?? null,
            ],
            'categories' => array_map(fn ($cat) => [
                'id' => $cat->term_id,
                'name' => $cat->name,
                'slug' => $cat->slug,
            ], $seed->categories()),
            'meta' => [
                'featured' => $seed->getMeta('featured', false),
            ],
        ];

        return new WP_REST_Response($data);
    }
}
