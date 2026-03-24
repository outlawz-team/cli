# PHP Code Style Guide

This guide outlines PHP coding conventions for WordPress development using Acorn (Laravel-powered WordPress). It emphasizes modern PHP practices while maintaining WordPress compatibility.

## Core principles

- **Modern PHP First**: Use PHP 8.4+ features (constructor property promotion, enums, property hooks, etc.)
- **PSR-12/PER-CS Compliant**: Follow modern PHP standards, not traditional WordPress Coding Standards
- **Laravel Conventions**: Leverage Laravel features for elegant solutions
- **Prefer Laravel Helpers**: Use `collect()`, `Str::` helpers, and other Laravel utilities over native PHP equivalents
- **Namespaces Required**: All PHP files must use proper namespaces (typically `namespace App;` or sub-namespaces)
- **CamelCase for Variables**: Use `$camelCase` for variables (WordPress core still uses `$snake_case`)
- **CamelCase for Methods**: Use `camelCase()` for class methods (WordPress functions still use `snake_case()`)
- **Short Echo Tags**: Always use `<?=` instead of `<?php echo`
- **REST API Only**: Never use `admin-ajax.php` - always use WordPress REST API endpoints
- **Modern Script Data**: Never use `wp_localize_script()` - use `wp_add_inline_script()` instead
- **No Yoda Conditions**: Write `$var === 'value'` not `'value' === $var`
- **No Space Padding**: `function($param)` not `function( $param )`
- **Short Array Syntax**: Always use `[]` instead of `array()`
- **Early Returns**: Use guard clauses and early returns to avoid nested conditionals
- **Meaningful Names**: Use descriptive variable and function names

## PHP syntax

### Variables and properties

```php
// ✅ Good - camelCase for variables
$postTypes = ['post', 'page'];
$cacheKey = 'custom_posts_'.$userId;
$isPublished = true;

// ❌ Bad - snake_case (except for WordPress core compatibility)
$post_types = ['post', 'page'];
$cache_key = 'custom_posts_' . $user_id;
```

### Arrays

```php
// ✅ Good - Short syntax with trailing commas
$config = [
    'post_types' => ['post', 'page'],
    'taxonomies' => ['category', 'tag'],
    'supports' => ['title', 'editor', 'thumbnail'],
];

// ✅ Good - Multi-line for readability
$args = [
    'post_type' => $postTypes,
    'post_status' => 'publish',
    'posts_per_page' => 10,
    'meta_query' => [
        [
            'key' => 'featured',
            'value' => '1',
            'compare' => '=',
        ],
    ],
];

// ❌ Bad - Old array syntax
$config = array(
    'post_types' => array('post', 'page')
);
```

### Functions and methods

```php
// ✅ Good - camelCase methods, type declarations, return types
public function getPublishedPosts(string $postType, int $limit = 10): Collection
{
    return collect(get_posts([
        'post_type' => $postType,
        'post_status' => 'publish',
        'numberposts' => $limit,
    ]));
}

// ✅ Good - Constructor property promotion
public function __construct(
    protected PostRepository $posts,
    protected CacheManager $cache,
    protected int $cacheTtl = 3600
) {}

// ✅ Good - Arrow functions for simple operations
$titles = $posts->map(fn ($post) => $post->post_title);
```

### Conditionals and control flow

#### Early returns (guard clauses)

```php
// ✅ Good - Early returns eliminate else
public function getPostStatus($post): string
{
    if (! $post) {
        return 'not_found';
    }
    
    if ($post->post_status === 'draft') {
        return 'draft';
    }
    
    return 'published';
}

// ❌ Bad - Unnecessary else statements
public function getPostStatus($post): string
{
    if (! $post) {
        return 'not_found';
    } else {
        if ($post->post_status === 'draft') {
            return 'draft';
        } else {
            return 'published';
        }
    }
}

// ✅ Good - Simple early return
public function getTitle($post): string
{
    if (! $post) {
        return 'Untitled';
    }
    
    return $post->post_title;
}

// ❌ Bad - Unnecessary else
public function getTitle($post): string
{
    if ($post) {
        return $post->post_title;
    } else {
        return 'Untitled';
    }
}
```

#### Other conditional patterns

```php
// ✅ Good - Null coalescing
$title = $post->post_title ?? 'Untitled';
$userId = auth()->id() ?? 0;

// ✅ Good - Ternary for simple conditions (but prefer null coalescing when applicable)
$class = $isActive ? 'active' : 'inactive';

// ✅ Good - Match expressions (PHP 8+)
$status = match ($post->post_status) {
    'publish' => 'Published',
    'draft' => 'Draft',
    'private' => 'Private',
    default => 'Unknown'
};

// ❌ Bad - Yoda conditions
if ('publish' === $post->post_status) {
    // Don't do this
}

// ❌ Bad - Unnecessary else
if ($condition) {
    return true;
} else {
    return false;
}

// ✅ Good - Just return the condition
return $condition;
```

## WordPress integration

### Hook registration

```php
// ✅ Good - Anonymous functions for simple hooks
add_action('init', function () {
    register_post_type('product', [
        'public' => true,
        'has_archive' => true,
        'supports' => ['title', 'editor', 'thumbnail'],
    ]);
});

// ✅ Good - Class methods for complex logic
class ProductManager
{
    public function boot(): void
    {
        add_action('init', [$this, 'registerPostType']);
        add_filter('the_content', [$this, 'filterContent']);
    }
    
    public function registerPostType(): void
    {
        // Complex registration logic
    }
}

// ✅ Good - Using closures with use statement
$prefix = 'custom_';
add_action('init', function () use ($prefix) {
    register_taxonomy($prefix.'category', ['post'], [
        'hierarchical' => true,
    ]);
});
```

### Working with posts

```php
// ✅ Good - Modern approach with type safety
public function getFeaturedPosts(int $count = 5): Collection
{
    $posts = get_posts([
        'numberposts' => $count,
        'meta_key' => 'is_featured',
        'meta_value' => '1',
        'post_status' => 'publish',
    ]);
    
    return collect($posts)->map(function ($post) {
        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'excerpt' => get_the_excerpt($post),
            'url' => get_permalink($post),
            'thumbnail' => get_the_post_thumbnail_url($post, 'medium'),
        ];
    });
}
```

### Filters and actions

```php
// ✅ Good - Namespace your hooks
apply_filters('app/posts/query_args', $args, $postType);
do_action('app/cache/cleared', $cacheKey);

// ✅ Good - Document hook parameters
/**
 * Filter post data before saving.
 *
 * @param  array  $data  Post data array
 * @param  int  $postId  Post ID
 */
$data = apply_filters('app/post/before_save', $data, $postId);
```

## Laravel/Acorn features

For detailed Acorn usage, refer to the [Acorn Documentation](https://roots.io/acorn/docs/).

### Service providers

Create service providers when you need to:
- Register services in the container (repositories, APIs, external services)
- Bootstrap application-level functionality (custom post types, taxonomies)
- Register WordPress hooks in an organized way
- Bind interfaces to implementations

```bash
# Create a new service provider
wp acorn make:provider CustomServiceProvider
```

**What to put in service providers:**
- **register()**: Bind classes to the container, merge config files
- **boot()**: WordPress hooks, route registration, view composers

**Don't put in service providers:**
- Business logic (put in services/repositories)
- WordPress template functions (put in helpers/functions)
- Direct database operations (put in models/repositories)

### Configuration

```php
// ✅ Good - Use config helper with defaults
$cacheTime = config('app.cache_ttl', 3600);
$features = config('app.features', []);

// ✅ Good - Structured configuration
return [
    'cache' => [
        'enabled' => env('CACHE_ENABLED', true),
        'ttl' => env('CACHE_TTL', 3600),
        'prefix' => env('CACHE_PREFIX', 'app_'),
    ],
    'features' => [
        'comments' => env('ENABLE_COMMENTS', true),
        'search' => env('ENABLE_SEARCH', true),
    ],
];
```

### Caching

```php
use Illuminate\Support\Facades\Cache;

// ✅ Good - Laravel caching
public function getPopularPosts(): Collection
{
    return Cache::remember('popular_posts', 3600, function () {
        return collect(get_posts([
            'meta_key' => 'view_count',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'numberposts' => 10,
        ]));
    });
}

// ✅ Good - Tagged caching
Cache::tags(['posts', 'homepage'])->remember($key, $ttl, $callback);
```

### Collections

```php
// ✅ Good - Leverage Laravel Collections
$posts = collect(get_posts($args))
    ->filter(fn ($post) => $post->post_status === 'publish')
    ->map(fn ($post) => $this->transformPost($post))
    ->sortByDesc('date')
    ->take(10);

// ✅ Good - Collection pipelines
$stats = collect($users)
    ->groupBy('role')
    ->map(fn ($group) => $group->count())
    ->sortDesc();
```

## 1st Party Package Development

For detailed package development patterns, see the [Acorn Package Development Documentation](https://roots.io/acorn/docs/).

### Composer setup for local packages

The key to loading your custom packages correctly is proper composer configuration:

```json
// Main application composer.json
{
    "repositories": [
        {
            "type": "path",
            "url": "./packages/*"
        }
    ],
    "require": {
        "app/your-package": "*"
    }
}
```

```json
// Package composer.json (packages/your-package/composer.json)
{
    "name": "app/your-package",
    "type": "library",
    "autoload": {
        "psr-4": {
            "App\\YourPackage\\": "src/"
        }
    },
    "extra": {
        "acorn": {
            "providers": [
                "App\\YourPackage\\Providers\\ServiceProvider"
            ]
        }
    }
}
```

After setting up composer configuration:

```bash
# Install the local package
composer install app/your-package

# Ensure Acorn recognizes the provider
wp acorn package:discover
```

## Asset management

### Enqueueing scripts and styles

```php
// ✅ Good - Modern asset management
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script(
        'app',
        get_template_directory_uri() . '/dist/js/app.js',
        [],
        '1.0.0',
        true
    );
    
    // Pass data to JavaScript using inline script (modern approach)
    $appData = [
        'apiUrl' => home_url('/wp-json/app/v1'),
        'nonce' => wp_create_nonce('wp_rest'),
        'user' => [
            'id' => get_current_user_id(),
            'can_edit' => current_user_can('edit_posts'),
        ],
    ];
    
    wp_add_inline_script(
        'app',
        'const appData = ' . wp_json_encode($appData) . ';',
        'before'
    );
});

// ✅ Good - Conditional loading
add_action('wp_enqueue_scripts', function () {
    if (is_singular('product')) {
        wp_enqueue_script('product-gallery', get_template_directory_uri() . '/dist/js/gallery.js', [], null, true);
    }
});

// ✅ Good - Modern modules
wp_enqueue_script_module(
    'app-module',
    get_template_directory_uri() . '/dist/js/module.js',
    ['@wordpress/interactivity'],
    null
);

// ❌ Bad - Legacy approach (never use)
// wp_localize_script('app', 'appData', $data);

/*
Why wp_localize_script() is outdated:
- Creates a global variable that can conflict with other scripts
- Forces data into a specific format/structure
- Less flexible for complex data types
- Can cause escaping issues with certain characters
- wp_add_inline_script() gives you full control over the JavaScript output
*/
```

## REST API development

### Modern vs legacy approaches

```php
// ❌ Bad - Never use admin-ajax.php (legacy approach)
add_action('wp_ajax_get_posts', function () {
    // Security checks
    if (! wp_verify_nonce($_POST['nonce'], 'get_posts_nonce')) {
        wp_die('Invalid nonce');
    }
    
    if (! current_user_can('read')) {
        wp_die('Insufficient permissions');
    }
    
    // Get posts
    $posts = get_posts(['numberposts' => 10]);
    
    wp_send_json_success($posts);
});

// ✅ Good - Use REST API instead
add_action('rest_api_init', function () {
    register_rest_route('app/v1', '/posts', [
        'methods' => 'GET',
        'callback' => function () {
            return get_posts(['numberposts' => 10]);
        },
        'permission_callback' => function () {
            return current_user_can('read');
        },
    ]);
});
```

```javascript
// ❌ Bad - Legacy JavaScript approach
jQuery.post(ajaxurl, {
    action: 'get_posts',
    nonce: ajax_object.nonce
});

// ✅ Good - Modern JavaScript approach
fetch('/wp-json/app/v1/posts', {
    headers: {
        'X-WP-Nonce': wpApiSettings.nonce
    }
});
```

### Registering endpoints

```php
// ✅ Good - Modern REST API usage
add_action('rest_api_init', function () {
    register_rest_route('app/v1', '/posts/featured', [
        'methods' => 'GET',
        'callback' => [new PostController, 'getFeatured'],
        'permission_callback' => '__return_true',
        'args' => [
            'count' => [
                'type' => 'integer',
                'default' => 5,
                'minimum' => 1,
                'maximum' => 20,
            ],
        ],
    ]);
});

// ✅ Good - Controller-based approach
class PostController
{
    public function getFeatured(\WP_REST_Request $request): \WP_REST_Response
    {
        $count = $request->get_param('count');
        
        $posts = Cache::remember("featured_posts_{$count}", 600, function () use ($count) {
            return $this->postRepository->getFeatured($count);
        });
        
        return rest_ensure_response([
            'posts' => $posts,
            'count' => $posts->count(),
        ]);
    }
}

// ❌ Bad - Never use admin-ajax.php
// add_action('wp_ajax_get_posts', 'handle_ajax_get_posts');
```

### Authentication

```php
// ✅ Good - Proper permission callbacks
register_rest_route('app/v1', '/user/profile', [
    'methods' => 'POST',
    'callback' => [$userController, 'updateProfile'],
    'permission_callback' => function () {
        return current_user_can('edit_own_profile');
    },
]);
```

## Database & migrations

See [Acorn Database Documentation](https://roots.io/acorn/docs/database/) for detailed information.

### Migrations

Use Acorn's migration commands for database schema management:

```bash
# Create a new migration
wp acorn make:migration create_custom_table

# Run pending migrations
wp acorn migrate

# Rollback the last batch of migrations
wp acorn migrate:rollback

# Check migration status
wp acorn migrate:status

# Reset and re-run all migrations (destructive)
wp acorn migrate:refresh

# Drop all tables and re-run migrations (destructive)
wp acorn migrate:fresh
```

For detailed migration syntax and Laravel database features, see the [Acorn Database Documentation](https://roots.io/acorn/docs/database/).

## Security patterns

### Nonce verification

```php
// ✅ Good - Always verify nonces for form submissions
if (! wp_verify_nonce($_POST['nonce'] ?? '', 'update_post_action')) {
    wp_die('Security check failed');
}

// ✅ Good - REST API with proper permission callback
add_action('rest_api_init', function () {
    register_rest_route('app/v1', '/update', [
        'methods' => 'POST',
        'callback' => [$controller, 'updateData'],
        'permission_callback' => function () {
            return current_user_can('edit_posts');
        },
        // WordPress REST API automatically handles nonce verification for POST requests
    ]);
});
```

### Capability checks

```php
// ✅ Good - Check user capabilities
public function updatePost(int $postId, array $data): bool
{
    if (! current_user_can('edit_post', $postId)) {
        throw new UnauthorizedException('Insufficient permissions');
    }
    
    return $this->postRepository->update($postId, $data);
}

// ✅ Good - Multiple capability checks
if (current_user_can('manage_options') || current_user_can('edit_others_posts')) {
    // Admin functionality
}
```

### Data sanitization

```php
// ✅ Good - Sanitize input data
public function savePostMeta(int $postId, array $meta): void
{
    $sanitized = [
        'title' => sanitize_text_field($meta['title'] ?? ''),
        'content' => wp_kses_post($meta['content'] ?? ''),
        'email' => sanitize_email($meta['email'] ?? ''),
        'url' => esc_url_raw($meta['url'] ?? ''),
        'number' => absint($meta['number'] ?? 0),
    ];
    
    foreach ($sanitized as $key => $value) {
        update_post_meta($postId, $key, $value);
    }
}

// ✅ Good - Validate and sanitize arrays
$allowedStatuses = ['draft', 'published', 'archived'];
$status = in_array($input['status'], $allowedStatuses) ? $input['status'] : 'draft';
```

### Output escaping

```php
// ✅ Good - Escape output based on context
echo esc_html($post->post_title);
echo esc_attr($post->post_excerpt);
echo esc_url($post->permalink);
echo wp_kses_post($post->post_content);

// ✅ Good - JSON output
wp_send_json_success([
    'message' => esc_html($message),
    'data' => array_map('esc_html', $data),
]);
```

## Error handling & debugging

### Development debugging

```php
// ✅ Good - Use Laravel's debug helpers
dd($variable); // Dump and die
dump($variable); // Dump and continue
ray($variable); // If Ray is installed

// ✅ Good - Conditional debugging
if (config('app.debug')) {
    \Log::debug('Processing post', ['id' => $postId, 'data' => $data]);
}

// ✅ Good - Try-catch with logging
try {
    $result = $this->processPayment($order);
} catch (\Exception $e) {
    \Log::error('Payment processing failed', [
        'order_id' => $order->id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    
    throw $e;
}
```

### Exception handling

```php
// ✅ Good - Custom exceptions
namespace App\Exceptions;

class PostNotFoundException extends \Exception
{
    public function __construct(int $postId)
    {
        parent::__construct("Post with ID {$postId} not found");
    }
}

// Usage
if (! $post = get_post($postId)) {
    throw new PostNotFoundException($postId);
}
```

## Code quality

### Type declarations

```php
// ✅ Good - Strict types and return type declarations
declare(strict_types=1);

namespace App\Services;

class PostService
{
    public function findById(int $id): ?Post
    {
        // Implementation
    }
    
    public function createFromArray(array $data): Post
    {
        // Implementation
    }
    
    public function getPublished(): Collection
    {
        // Implementation
    }
}
```

### Method organization

```php
class ExampleClass
{
    // 1. Properties
    protected string $name;
    private CacheManager $cache;
    
    // 2. Constructor
    public function __construct(string $name, CacheManager $cache)
    {
        $this->name = $name;
        $this->cache = $cache;
    }
    
    // 3. Public methods
    public function process(): void
    {
        // Implementation
    }
    
    // 4. Protected methods
    protected function validate(): bool
    {
        // Implementation
    }
    
    // 5. Private methods
    private function transform(array $data): array
    {
        // Implementation
    }
}
```

### Documentation

```php
/**
 * Process and cache post data.
 *
 * @param  Collection  $posts Collection of WP_Post objects
 * @param  bool  $forceRefresh Whether to bypass cache
 * @return array Processed post data
 * @throws PostProcessingException
 */
public function processPosts(Collection $posts, bool $forceRefresh = false): array
{
    // Implementation
}
```
