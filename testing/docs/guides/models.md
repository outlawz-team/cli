# Working with models

Radicle includes Eloquent models for working with WordPress data in a Laravel-style way. These models provide a clean, object-oriented interface to WordPress posts, custom post types, and meta data.

## Available models

Radicle comes with the following models out of the box:

- `Post` — WordPress posts with standard blog functionality
- `Seed` — Custom post type extending Post with seed-specific methods
- `PostMeta` — Post meta data relationships

## Basic usage

### Getting posts and seeds

```php
use App\Models\Post;
use App\Models\Seed;

// Get all published posts
$posts = Post::published()->get();

// Get recent posts
$recentPosts = Post::recent(5)->get();

// Get all published seeds
$seeds = Seed::published()->get();

// Find specific post/seed by ID
$post = Post::find(123);
$seed = Seed::find(456);
```

### Working with post data

```php
$post = Post::find(123);

// Basic post data
echo $post->title();
echo $post->content();
echo $post->excerpt(30); // 30 words
echo $post->permalink();

// Thumbnails and images
if ($post->hasThumbnail()) {
    echo $post->thumbnail('large');
}

// Meta data
$customField = $post->getMeta('custom_field', 'default_value');

// Categories and tags
$categories = $post->categories();
$tags = $post->tags();

// Dates
echo $post->date('F j, Y');
echo $post->modifiedDate('F j, Y');
```

### Seed-specific methods

```php
$seed = Seed::find(123);

// Seed categories (uses seed_category taxonomy)
$categories = $seed->categories();

// Navigation between seeds
$nextSeed = $seed->nextSeed();
$previousSeed = $seed->previousSeed();
```

## Using models in view composers

Models work great with view composers to aggregate content for templates:

```php
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
            'featuredPost' => Post::published()->first(),
            'recentPosts' => Post::recent(6)->get(),
            'featuredSeeds' => Seed::recent(4)->get(),
            'totalPosts' => Post::published()->count(),
            'totalSeeds' => Seed::published()->count(),
        ];
    }
}
```

## Using models in templates

In your Blade templates, you can work with the model data:

```blade
@foreach ($recentPosts as $post)
  <article>
    @if ($post->hasThumbnail())
      <img src="{{ $post->thumbnail('medium') }}" alt="{{ $post->title() }}">
    @endif

    <h2><a href="{{ $post->permalink() }}">{{ $post->title() }}</a></h2>
    <p>{{ $post->excerpt(20) }}</p>

    <div class="meta">
      <time>{{ $post->date('M j, Y') }}</time>
      @foreach ($post->categories() as $category)
        <span>{{ $category->name }}</span>
      @endforeach
    </div>
  </article>
@endforeach
```

## Demo implementation

Radicle includes a demo `front-page.blade.php` template that showcases the models in action. It demonstrates:

- Featuring the latest post in a hero section
- Displaying recent posts in a grid
- Showcasing featured seeds
- Aggregating site statistics

The front page uses the `FrontPage` view composer to gather all the necessary data using the Eloquent models.

## Meta relationships

The `PostMeta` model handles WordPress post meta data:

```php
// Access meta through relationships
$post = Post::find(123);
$allMeta = $post->meta; // Collection of PostMeta models

// Or use the helper method
$customValue = $post->getMeta('custom_field', 'default');
```

## Database integration

The models work directly with WordPress database tables:

- `Post` and `Seed` use the `wp_posts` table
- `PostMeta` uses the `wp_postmeta` table
- Global scopes automatically filter by `post_type`
- Proper relationships between posts and meta data

This gives you the power of Eloquent while maintaining full WordPress compatibility.
