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

    'default_plugins' => ['classic-editor', 'wp-mail-smtp', 'wp-mail-logging', 'advanced-custom-fields', 'yoast-seo', 'post-duplicator'],

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
            'require' => ['wp-plugin/woocommerce'],
        ],
        'imagify' => [
            'key' => 'imagify',
            'name' => 'Imagify',
            'require' => ['wp-plugin/imagify'],
        ],
        'jetformbuilder' => [
            'key' => 'jetformbuilder',
            'name' => 'JetFormBuilder',
            'require' => ['wp-plugin/jetformbuilder'],
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
            'require' => ['wp-plugin/wordpress-seo'],
        ],
        'classic-editor' => [
            'key' => 'classic-editor',
            'name' => 'Classic Editor',
            'require' => ['wp-plugin/classic-editor'],
        ],
        'wp-mail-smtp' => [
            'key' => 'wp-mail-smtp',
            'name' => 'WP Mail SMTP',
            'require' => ['wp-plugin/wp-mail-smtp'],
        ],
        'wp-mail-logging' => [
            'key' => 'wp-mail-logging',
            'name' => 'WP Mail Logging',
            'require' => ['wp-plugin/wp-mail-logging'],
        ],
        'post-duplicator' => [
            'key' => 'post-duplicator',
            'name' => 'Post Duplicator',
            'require' => ['wp-plugin/post-duplicator'],
        ],
    ],

];
