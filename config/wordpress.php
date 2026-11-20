<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WordPress default plugins
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default WordPress plugins that can be installed.
    |
    */

    'default_plugins' => [],

    /*
    |--------------------------------------------------------------------------
    | Fixed WordPress plugins
    |--------------------------------------------------------------------------
    |
    | The plugins every project has: they are in composer.json of
    | outlawz-team/radicle-boilerplate. Only to say so; a change is a change
    | in the boilerplate.
    |
    */

    'fixed_plugins' => ['Advanced Custom Fields Pro', 'JetFormBuilder', 'Imagify', 'Yoast SEO', 'Classic Editor', 'WP Mail SMTP', 'WP Mail Logging', 'Post Duplicator', 'Safe SVG'],

    /*
    |--------------------------------------------------------------------------
    | WordPress Plugins
    |--------------------------------------------------------------------------
    |
    | The WordPress plugins to choose per project.
    |
    */

    'plugins' => [
        'woocommerce' => [
            'key' => 'woocommerce',
            'name' => 'WooCommerce',
            'require' => ['wp-plugin/woocommerce'],
        ],
    ],

];
