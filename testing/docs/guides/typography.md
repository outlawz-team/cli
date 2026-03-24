# Typography components

Typography components provide consistent text styling across your Radicle application. These components centralize typography classes and ensure design consistency.

## Why use typography components?

Instead of repeating Tailwind classes throughout templates:

```blade
{{-- ❌ Inconsistent styling --}}
<h1 class="text-3xl font-semibold text-gray-900">Title</h1>
<h2 class="text-2xl font-semibold text-gray-900">Subtitle</h2>
<a href="/page" class="text-blue-600 hover:text-blue-800 underline">Link</a>
```

Use centralized components:

```blade
{{-- ✅ Consistent, maintainable styling --}}
<x-heading level="h1" size="3xl">Title</x-heading>
<x-heading level="h2" size="2xl">Subtitle</x-heading>
<x-link href="/page">Link</x-link>
```

## Heading component

The `<x-heading>` component provides consistent heading styles with flexible sizing.

### Basic usage

```blade
<x-heading level="h1" size="3xl">Page Title</x-heading>
<x-heading level="h2" size="2xl">Section Title</x-heading>
<x-heading level="h3" size="xl">Subsection</x-heading>
```

### Props

| Prop | Type | Default | Options |
|------|------|---------|---------|
| `level` | string | `h2` | `h1`, `h2`, `h3`, `h4`, `h5`, `h6` |
| `size` | string | Auto-sized by level | `xs`, `sm`, `md`, `lg`, `xl`, `2xl`, `3xl` |
| `color` | string | `default` | `default`, `black`, `white`, `muted` |

### Size mapping

If no `size` is specified, headings use these defaults:

- `h1` → `xl` (text-2xl)
- `h2` → `lg` (text-xl)
- `h3` → `md` (text-lg)
- `h4` → `sm` (text-base)
- `h5`, `h6` → `xs` (text-sm)

### Examples

```blade
{{-- Auto-sized headings --}}
<x-heading level="h1">Auto-sized H1</x-heading>
<x-heading level="h2">Auto-sized H2</x-heading>

{{-- Custom sizing --}}
<x-heading level="h3" size="2xl">Large H3</x-heading>
<x-heading level="h1" size="sm">Small H1</x-heading>

{{-- Colors --}}
<x-heading level="h2" color="white">White heading</x-heading>
<x-heading level="h3" color="muted">Muted heading</x-heading>
```

## Link component

The `<x-link>` component handles internal and external links with consistent styling.

### Basic usage

```blade
<x-link href="/page">Internal link</x-link>
<x-link href="https://example.com" external>External link</x-link>
```

### Props

| Prop | Type | Default | Options |
|------|------|---------|---------|
| `href` | string | `#` | Any valid URL |
| `variant` | string | `default` | `default`, `unstyled` |
| `weight` | string | `normal` | `light`, `normal`, `medium`, `semibold`, `bold` |
| `external` | boolean | `false` | Adds `target="_blank"` and external icon |

### Variants

**Default**: Blue links with underlines
```blade
<x-link href="/page">Default link</x-link>
```

**Unstyled**: Inherits parent styling, useful for styled headings
```blade
<x-heading level="h2">
    <x-link href="/post" variant="unstyled">Post Title</x-link>
</x-heading>
```

### External links

External links automatically get `target="_blank"`, `rel="noopener noreferrer"`, and an external icon:

```blade
<x-link href="https://github.com/roots/radicle" external>
    View on GitHub
</x-link>
```

## List components

The `<x-list>` and `<x-list-item>` components provide consistent list styling.

### Basic usage

```blade
<x-list type="ul" spacing="normal">
    <x-list-item>First item</x-list-item>
    <x-list-item>Second item</x-list-item>
    <x-list-item>Third item</x-list-item>
</x-list>
```

### List props

| Prop | Type | Default | Options |
|------|------|---------|---------|
| `type` | string | `ul` | `ul`, `ol` |
| `spacing` | string | `normal` | `tight`, `normal`, `loose` |
| `style` | string | `default` | `default`, `none`, `inside`, `outside` |

### List item props

| Prop | Type | Default | Options |
|------|------|---------|---------|
| `spacing` | string | `normal` | `tight`, `normal`, `loose` |

### Examples

```blade
{{-- Ordered list with tight spacing --}}
<x-list type="ol" spacing="tight">
    <x-list-item>Step one</x-list-item>
    <x-list-item>Step two</x-list-item>
    <x-list-item>Step three</x-list-item>
</x-list>

{{-- Unstyled list --}}
<x-list type="ul" style="none">
    <x-list-item>Clean item</x-list-item>
    <x-list-item>No bullets</x-list-item>
</x-list>
```

## WordPress editor styling

The `resources/views/blocks/global.theme.js` file provides global typography styles for the WordPress block editor, ensuring consistent styling across both the frontend and backend editing experience.

## Best practices

### Component selection

- **Use `<x-heading>`** for all headings instead of raw `<h1>-<h6>` tags
- **Use `<x-link>`** for all links to ensure consistent styling and external link handling
- **Use `<x-list>`** for structured lists that need consistent spacing

### Semantic HTML

Always choose the correct semantic level regardless of visual size:

```blade
{{-- ✅ Correct: Semantic level with visual override --}}
<x-heading level="h3" size="xl">Large H3</x-heading>

{{-- ❌ Wrong: Visual level over semantic meaning --}}
<x-heading level="h1" size="md">Small but semantically H1</x-heading>
```

### Link variants

- **Default variant**: Use for regular content links
- **Unstyled variant**: Use inside styled containers like headings or buttons

```blade
{{-- ✅ Good: Unstyled link in heading --}}
<x-heading level="h2">
    <x-link href="/post" variant="unstyled">Post Title</x-link>
</x-heading>

{{-- ❌ Avoid: Default styling conflicts with heading --}}
<x-heading level="h2">
    <x-link href="/post">Post Title</x-link>
</x-heading>
```

## Real-world examples

### Blog post listing

```blade
<article>
    <x-heading level="h2" size="xl" class="mb-3">
        <x-link href="{{ $post->permalink() }}" variant="unstyled">
            {{ $post->title() }}
        </x-link>
    </x-heading>

    <p class="mb-4">{{ $post->excerpt() }}</p>

    <x-link href="{{ $post->permalink() }}">Read more</x-link>
</article>
```

### Navigation list

```blade
<nav>
    <x-list type="ul" style="none" spacing="tight">
        <x-list-item>
            <x-link href="/">Home</x-link>
        </x-list-item>
        <x-list-item>
            <x-link href="/about">About</x-link>
        </x-list-item>
        <x-list-item>
            <x-link href="/contact">Contact</x-link>
        </x-list-item>
    </x-list>
</nav>
```

### Feature list

```blade
<section>
    <x-heading level="h2" size="2xl" class="mb-4">Features</x-heading>

    <x-list type="ul" spacing="loose">
        <x-list-item>Fast development with Laravel tools</x-list-item>
        <x-list-item>Modern WordPress architecture</x-list-item>
        <x-list-item>Built-in testing framework</x-list-item>
    </x-list>
</section>
```
