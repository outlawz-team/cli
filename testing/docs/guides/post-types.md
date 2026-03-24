# Registering post types and taxonomies

The `config/post-types.php` file is used to register custom post types and taxonomies.

Radicle includes the [Extended CPTs](https://github.com/johnbillion/extended-cpts) library along with a post types service provider to allow for configuring post types and taxonomies from a config.

By default, a `seed` post type and `seed_category` taxonomy are registered. Remove or replace these with ones that work for your site.

All of the parameters from Extended CPTs are supported for both [post types](https://github.com/johnbillion/extended-cpts/wiki/Registering-Post-Types) and [taxonomies](https://github.com/johnbillion/extended-cpts/wiki/Registering-taxonomies).

## Registering multiple post types

In `config/post-types.php` within the `post_types` array, add additional array keys to register multiple post types. In the example below, we are registering two post types: `seed` and `product`.

```php
<?php

return [
    'post_types' => [
        'seed' => [
            'menu_icon' => 'dashicons-star-filled',
            'supports' => ['title', 'editor', 'author', 'revisions', 'thumbnail'],
            'show_in_rest' => true,
            'names' => [
                'singular' => 'Seed',
                'plural' => 'Seeds',
                'slug' => 'seeds',
            ]
        ],
        'product' => [
            'menu_icon' => 'dashicons-cart',
            'supports' => ['title', 'editor', 'author', 'revisions', 'thumbnail'],
            'show_in_rest' => true,
        ],
    ],
];
```

## Registering taxonomies

Similar to post types, taxonomies are configured in the same `config/post-types.php` file using the `taxonomies` array. The `seed_category` taxonomy is registered by default and associated with the `seed` post type.