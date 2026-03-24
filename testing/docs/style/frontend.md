# Frontend Development Guide

This guide covers HTML, CSS, and JavaScript conventions for Radicle projects using Blade templates, Tailwind CSS, Alpine.js, and Vite.

## Tech stack overview

- **Blade Templates** - Laravel templating for HTML
- **Tailwind CSS** - Utility-first CSS framework
- **Alpine.js** - Lightweight JavaScript framework
- **Vite** - Build tool with Hot Module Replacement
- **Heroicons** - SVG icons via Blade UI Kit

## HTML / Blade templates

See existing components in `resources/views/components/` and templates in `resources/views/`.

## CSS / Tailwind

See `resources/css/app.css` for Tailwind configuration.

### Avoid `@apply` (usually)

Prefer utilities directly in templates over `@apply`:

```php
{{-- ✅ Use utilities in templates --}}
<button class="px-4 py-2 bg-blue-500 hover:bg-blue-600">Button</button>
```

```css
/* ❌ Avoid @apply for your own components */
.btn {
  @apply px-4 py-2 bg-blue-500;
}
```

**Exception**: Use `@apply` when you need to override WordPress plugin styles you can't control:

```css
/* ✅ OK for overriding plugin styles */
.some-plugin-class {
  @apply text-sm text-gray-600 p-4;
}
```

## JavaScript / Alpine.js

Basic Alpine patterns:

```php
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open" x-transition>Content</div>
</div>
```

See existing components in `resources/js/` and Alpine usage in `resources/views/components/`.

## Build system

See `vite.config.js` for configuration. Development commands:

```bash
npm run dev    # Development server with HMR
npm run build  # Production build
```

## Icons

Use Heroicons: `<x-heroicon-o-x-mark class="w-5 h-5" />`

## Code formatting

The project uses EditorConfig (see [`.editorconfig`](../../.editorconfig)) for consistent formatting.

## Best practices

- Use `x-cloak` to prevent layout shift
- Escape output with `{{ }}` vs `{!! !!}`
- Use translation functions: `{{ __('Text', 'radicle') }}`
- Include proper ARIA attributes and semantic HTML

See existing components in `resources/views/components/` for reference implementations.
