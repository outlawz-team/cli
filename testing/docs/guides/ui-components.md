# UI components

Radicle includes several pre-built UI components for common interface patterns. These components provide consistent styling and functionality across your application.

## Button component

The `<x-button>` component provides consistent button styling with multiple variants and sizes.

### Basic usage

```blade
<x-button href="/page">Primary Button</x-button>
<x-button variant="outline" href="/page">Outline Button</x-button>
<x-button variant="inverse" href="/page">Inverse Button</x-button>
```

### Props

| Prop | Type | Default | Options |
|------|------|---------|---------|
| `variant` | string | `primary` | `primary`, `outline`, `inverse` |
| `size` | string | `base` | `xs`, `sm`, `base`, `lg` |
| `element` | string | Auto-detected | `a`, `button` |
| `href` | string | - | Required for `a` elements |

### Variants

**Primary**: Black background with white text
```blade
<x-button href="/action">Primary Action</x-button>
```

**Outline**: Transparent background with black border
```blade
<x-button variant="outline" href="/action">Secondary Action</x-button>
```

**Inverse**: White background with black text (for dark backgrounds)
```blade
<section class="bg-black text-white">
    <x-button variant="inverse" href="/action">Inverse Button</x-button>
</section>
```

### Sizes

```blade
<x-button size="xs" href="#">Extra Small</x-button>
<x-button size="sm" href="#">Small</x-button>
<x-button size="base" href="#">Base (Default)</x-button>
<x-button size="lg" href="#">Large</x-button>
```

### Button vs Link elements

The component automatically chooses the correct HTML element:

```blade
{{-- Renders as <a> tag --}}
<x-button href="/page">Link Button</x-button>

{{-- Renders as <button> tag --}}
<x-button element="button" type="submit">Form Button</x-button>
```

## Modal component

The `<x-modal>` component creates accessible modal dialogs with Alpine.js.

### Basic usage

```blade
<x-modal title="Modal Title">
    <x-slot name="button">Open Modal</x-slot>

    <p>Modal content goes here.</p>
</x-modal>
```

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `title` | string | - | Modal header title |

### Slots

- **Default slot**: Modal content
- **`button` slot**: Trigger button content

### Example with form

```blade
<x-modal title="Contact Form">
    <x-slot name="button">Contact Us</x-slot>

    <form action="/contact" method="POST">
        @csrf
        <div class="mb-4">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>

        <x-button element="button" type="submit">Send Message</x-button>
    </form>
</x-modal>
```

## Alert component

The `<x-alert>` component displays contextual messages with different types.

### Basic usage

```blade
<x-alert type="success">Operation completed successfully!</x-alert>
<x-alert type="warning">Please review your input.</x-alert>
<x-alert type="error">An error occurred.</x-alert>
```

### Props

| Prop | Type | Default | Options |
|------|------|---------|---------|
| `type` | string | `info` | `success`, `warning`, `error`, `info` |

## Table component

The `<x-table>` component creates responsive tables with consistent styling.

### Basic usage

```blade
<x-table
    :columns="['Name', 'Email', 'Role']"
    :rows="[
        ['John Doe', 'john@example.com', 'Admin'],
        ['Jane Smith', 'jane@example.com', 'User'],
    ]"
/>
```

### Props

| Prop | Type | Description |
|------|------|-------------|
| `columns` | array | Table header columns |
| `rows` | array | Table data rows |

### Dynamic content

```blade
<x-table
    :columns="['Post', 'Author', 'Date']"
    :rows="$posts->map(fn($post) => [
        $post->title(),
        $post->author(),
        $post->date(),
    ])->toArray()"
/>
```

## Best practices

### Component consistency

- **Always use components** instead of custom HTML when a component exists
- **Follow component APIs** - don't override styling with custom classes unless necessary
- **Use semantic variants** - choose the variant that matches the context

### Accessibility

- **Modal components** automatically handle focus management and keyboard navigation
- **Button components** maintain proper button/link semantics
- **Alert components** include appropriate ARIA attributes

### Performance

- **Conditional loading**: Components only load their assets when used
- **Lightweight markup**: Components generate minimal HTML
- **No JavaScript dependencies**: Except where needed (modals use Alpine.js)

## Examples in context

### Call-to-action section

```blade
<section class="bg-black text-white p-8 text-center">
    <x-heading level="h2" size="2xl" color="white" class="mb-4">
        Ready to get started?
    </x-heading>

    <p class="text-white text-lg mb-6">
        Join thousands of developers building with Radicle.
    </p>

    <div class="flex gap-4 justify-center">
        <x-button variant="inverse" href="/signup">Get Started</x-button>
        <x-button variant="outline" href="/docs">Learn More</x-button>
    </div>
</section>
```

### Data table with actions

```blade
<section>
    <x-heading level="h2" size="xl" class="mb-4">Recent Orders</x-heading>

    <x-table
        :columns="['Order ID', 'Customer', 'Total', 'Actions']"
        :rows="$orders->map(fn($order) => [
            '#' . $order->id,
            $order->customer_name,
            '$' . number_format($order->total, 2),
            '<x-button size=\"sm\" href=\"/orders/' . $order->id . '\">View</x-button>'
        ])->toArray()"
    />
</section>
```

### Status messages

```blade
@if (session('success'))
    <x-alert type="success">{{ session('success') }}</x-alert>
@endif

@if (session('error'))
    <x-alert type="error">{{ session('error') }}</x-alert>
@endif

@if ($errors->any())
    <x-alert type="warning">
        Please correct the following errors:
        <x-list type="ul" spacing="tight" class="mt-2">
            @foreach ($errors->all() as $error)
                <x-list-item>{{ $error }}</x-list-item>
            @endforeach
        </x-list>
    </x-alert>
@endif
```