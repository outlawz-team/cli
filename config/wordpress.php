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

    'default_plugins' => ['classic-editor', 'wp-mail-smtp', 'wp-mail-logging', 'advanced-custom-fields', 'yoast-seo'],

    /*
    |--------------------------------------------------------------------------
    | WordPress Plugins
    |--------------------------------------------------------------------------
    |
    | Here you may specify the WordPress plugins that can be installed.
    |
    */

    'plugins' => [
        'woocommerce' => [
            'key' => 'woocommerce',
            'name' => 'WooCommerce',
            'require' => ['wpackagist-plugin/woocommerce'],
        ],
        'imagify' => [
            'key' => 'imagify',
            'name' => 'Imagify',
            'require' => ['wpackagist-plugin/imagify'],
        ],
        'jetformbuilder' => [
            'key' => 'jetformbuilder',
            'name' => 'JetFormBuilder',
            'require' => ['wpackagist-plugin/jetformbuilder'],
        ],
        'advanced-custom-fields' => [
            'key' => 'advanced-custom-fields',
            'name' => 'Advanced Custom Fields',
            'repositories' => [['type' => 'composer', 'url' => 'https://connect.advancedcustomfields.com']],
            'require' => ['wpengine/advanced-custom-fields-pro', 'stoutlogic/acf-builder'],
        ],
        'yoast-seo' => [
            'key' => 'yoast-seo',
            'name' => 'Yoast SEO',
            'require' => ['wpackagist-plugin/wordpress-seo'],
        ],
        'classic-editor' => [
            'key' => 'classic-editor',
            'name' => 'Classic Editor',
            'require' => ['wpackagist-plugin/classic-editor'],
        ],
        'wp-mail-smtp' => [
            'key' => 'wp-mail-smtp',
            'name' => 'WP Mail SMTP',
            'require' => ['wpackagist-plugin/wp-mail-smtp'],
        ],
        'wp-mail-logging' => [
            'key' => 'wp-mail-logging',
            'name' => 'WP Mail Logging',
            'require' => ['wpackagist-plugin/wp-mail-logging'],
        ],
    ],

];
