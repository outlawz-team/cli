# Service provider configuration

Radicle uses service providers for configuring and bootstraping various services.

> [!WARNING]
> **Unfamiliar with service providers?** We suggest [reading the Laravel documentation on service providers](https://laravel.com/docs/10.x/providers)

Radicle comes with the following service providers out of the box:

* `AssetsServiceProvider` — Used for WordPress assets (CSS and JS) registration
* `BlocksServiceProvider` — Used for registering first-party block renderers from `app/Blocks/`
* `ThemeServiceProvider` — Used for registering [theme supports](https://developer.wordpress.org/reference/functions/add_theme_support/) and sidebars from a config

These service providers are loaded from the `composer.json` file:

```json
...
    "extra": {
        "acorn": {
            "providers": [
                "App\\Providers\\AssetsServiceProvider",
                "App\\Providers\\BlocksServiceProvider",
                "App\\Providers\\ThemeServiceProvider"
            ]
        },
...
```

You can add/modify/remove these service providers. If adding or removing providers, we recommend changing the `composer.json` file.

Acorn's CLI supports creating a new service provider class:

```shell
$ wp acorn make:provider ExampleProvider
```

After creating a new provider class, make sure to add it to the `providers` array in `composer.json`.

It is also possible to use the `config/app.php` file and modify the `providers` array to manage autoloaded service providers.

## Block organization

Radicle organizes block rendering logic in the `app/Blocks/` directory:

```
app/Blocks/
├── Core/          # WordPress core block customizations
│   └── Button.php
├── Modal.php      # First-party radicle/* blocks
└── ...            # Additional first-party blocks
```

Block classes handle the rendering logic and are automatically registered by the `BlocksServiceProvider`. Each block class should have a `render()` method that takes the block content and block data as parameters.

**Example block class:**
```php
<?php

namespace App\Blocks;

class Modal
{
    public function render(string $blockContent, array $block): string
    {
        if ($block['blockName'] !== 'radicle/modal') {
            return $blockContent;
        }

        return view('blocks.modal', [
            'block' => $block,
            'blockContent' => $blockContent,
            // ... additional data
        ]);
    }
}
```

Block templates can be found in the `resources/views/blocks/` directory.
