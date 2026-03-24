# Block Editor (Gutenberg) Development Guide

This guide covers developing custom blocks and working with the WordPress Block Editor in Radicle projects.

Radicle uses server-side rendering for blocks with Blade templates. See `app/Providers/BlocksServiceProvider.php` for registration patterns.

## Creating blocks

### Using the `make:block` command

Radicle includes an Acorn command to scaffold new blocks automatically:

```bash
# Interactive mode - prompts for block name
wp acorn make:block

# Or specify the name directly
wp acorn make:block my-custom-block
```

This command creates all necessary files:
- **PHP Block Class**: `app/Blocks/MyCustomBlock.php` - Server-side rendering logic
- **JavaScript Block**: `resources/js/editor/my-custom-block.block.jsx` - Editor component
- **Blade Template**: `resources/views/blocks/my-custom-block.blade.php` - Frontend template
- **Auto-registration**: Updates `BlocksServiceProvider.php` and `editor.js`

The command handles name formatting automatically:
- Converts input to StudlyCase for class names
- Creates kebab-case for file names and block identifiers
- Registers the block in both PHP and JavaScript

### Manual block creation

For more control over the block structure, you can create blocks manually:

### 1. JavaScript block definition

Create in `resources/js/editor/my-block.block.jsx`:

```javascript
import { InspectorControls, useBlockProps } from "@wordpress/block-editor";
import { PanelBody, TextControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";

export const name = `radicle/my-block`;
export const title = __(`My Block`, `radicle`);
export const category = `design`;

export const attributes = {
  title: {
    type: `string`,
    default: __(`Block Title`, `radicle`),
  },
};

export const edit = ({ attributes, setAttributes }) => {
  return (
    <>
      <InspectorControls>
        <PanelBody title={__(`Settings`, `radicle`)}>
          <TextControl
            label={__(`Title`, `radicle`)}
            value={attributes.title}
            onChange={(title) => setAttributes({ title })}
          />
        </PanelBody>
      </InspectorControls>

      <div {...useBlockProps()}>
        <h3>{attributes.title}</h3>
      </div>
    </>
  );
};

export const save = () => null; // Server-side rendering
```

### 2. PHP Registration

Add to `BlocksServiceProvider`:

```php
add_filter('render_block_radicle/my-block', function ($blockContent, $block) {
    return view('blocks.my-block', [
        'title' => sanitize_text_field($block['attrs']['title'] ?? 'Block Title'),
    ]);
}, 10, 2);
```

### 3. Blade Template

Create `resources/views/blocks/my-block.blade.php`:

```php
<div class="my-block">
    <h3>{{ $title }}</h3>
    <p>Custom block content</p>
</div>
```

## Dynamic blocks

Dynamic blocks render content that changes based on database queries or user settings. The Latest Seeds block demonstrates this pattern:

### JavaScript definition with controls

Dynamic blocks use Inspector Controls for user configuration:

```javascript
import { InspectorControls, useBlockProps } from "@wordpress/block-editor";
import { RangeControl, ToggleControl, RadioControl } from "@wordpress/components";
import { useSelect } from "@wordpress/data";
import { store as coreStore } from "@wordpress/core-data";

export const attributes = {
  posts: { type: 'number', default: 5 },
  displayFeaturedImage: { type: 'boolean', default: false },
  postLayout: { type: 'string', default: 'list' },
  displayPostContent: { type: 'string', default: 'none' },
};

export const edit = ({ attributes, setAttributes }) => {
  const { posts, displayFeaturedImage, postLayout, displayPostContent } = attributes;

  // Fetch data dynamically
  const { latestSeeds } = useSelect((select) => {
    const { getEntityRecords } = select(coreStore);
    return {
      latestSeeds: getEntityRecords('postType', 'seed', {
        per_page: posts,
        _embed: 'wp:featuredmedia',
        order: 'desc',
        orderby: 'date',
      }),
    };
  }, [posts]);

  return (
    <>
      <InspectorControls>
        <RangeControl
          label="Number of seeds"
          value={posts}
          onChange={(value) => setAttributes({ posts: value })}
          min={1}
          max={10}
        />
        <ToggleControl
          label="Display featured image"
          checked={displayFeaturedImage}
          onChange={(value) => setAttributes({ displayFeaturedImage: value })}
        />
        <RadioControl
          label="Layout"
          selected={postLayout}
          options={[
            { label: 'List', value: 'list' },
            { label: 'Grid', value: 'grid' },
          ]}
          onChange={(value) => setAttributes({ postLayout: value })}
        />
      </InspectorControls>

      <div {...useBlockProps()}>
        {/* Render preview with fetched data */}
      </div>
    </>
  );
};
```

### Server-side rendering with dynamic data

In `BlocksServiceProvider`, query data and pass to Blade template:

```php
add_filter('render_block_radicle/latest-seeds', function ($block_content, $block) {
    $attributes = $block['attrs'] ?? [];
    $posts = $attributes['posts'] ?? 5;
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
        'displayFeaturedImage' => $displayFeaturedImage,
        // ... other attributes
    ]);
}, 10, 2);
```

### Conditional layouts in Blade

Use PHP arrays to manage different layout configurations:

```php
@php
$layoutConfig = [
    'grid' => [
        'container' => 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6',
        'item' => 'bg-white border border-gray-200 overflow-hidden',
        'element' => 'div',
    ],
    'list' => [
        'container' => 'list-none p-0 m-0 space-y-6',
        'item' => 'pb-6 border-b border-gray-200 flex gap-4',
        'element' => 'ul',
    ]
];
$config = $layoutConfig[$postLayout];
@endphp

<{{ $config['element'] }} class="{{ $config['container'] }}">
    @foreach ($seeds as $seed)
        <!-- Render each item -->
    @endforeach
</{{ $config['element'] }}>
```

## Block patterns

Register reusable patterns in `BlocksServiceProvider`:

```php
add_action('init', function () {
    register_block_pattern('radicle/hero', [
        'title' => __('Hero Section', 'radicle'),
        'categories' => ['radicle-layouts'],
        'content' => '<!-- wp:heading {"level":1} --><h1>Hero Title</h1><!-- /wp:heading -->'
    ]);
});
```

## Block style variations

Add custom styles for existing blocks:

```php
add_action('init', function () {
    register_block_style('core/button', [
        'name' => 'outline',
        'label' => __('Outline', 'radicle'),
    ]);
});
```

## Overriding core blocks

Override core blocks with Blade components (see existing modal block in codebase):

```php
add_filter('render_block_core/button', function ($blockContent, $block) {
    return view('blocks.button', ['text' => 'Button Text']);
}, 10, 2);
```

## Best practices

- **Security**: Always sanitize block attributes
- **Performance**: Load block assets conditionally with `has_block()`
- **Accessibility**: Include proper ARIA labels
- **Testing**: Write E2E tests for block functionality

See existing blocks in `resources/js/editor/` for reference implementations.
