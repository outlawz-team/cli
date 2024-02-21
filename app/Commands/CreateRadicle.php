<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\progress;

class CreateRadicle extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'create:radicle {folder : The folder to create the project in.}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create a new radicle project.';

    /**
     * Default plugins
     */
    protected $defaultPlugins = [
        'classic-editor', 'wp-mail-smtp', 'wp-mail-logging', 'advanced-custom-fields', 'yoast-seo'
    ];

    /**
     * Plugins
     */
    protected function plugins()
    {
        return [
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
                'auth' => [
                    'http-basic' => [
                        'connect.advancedcustomfields.com' => [
                            'username' => 'b3JkZXJfaWQ9MTI4NDIwfHR5cGU9ZGV2ZWxvcGVyfGRhdGU9MjAxOC0wNC0wNCAxMToyNTo1Ng',
                            'password' => "{$this->folder}.test"
                        ]
                    ]
                ]
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
        ];
    }
    

    /**
     * Folder
     */
    protected $folder;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        info('Creating a new Radicle project...');

        $this->folder = $this->argument('folder');
        
        $plugins = multiselect(
            label: 'Which plugins would you like to include?',
            options: array_column($this->plugins(), 'name', 'key'),
            default: $this->defaultPlugins
        );

        if(!confirm('Are you sure you want to continue?')){
            error('Radicle project creation cancelled.');
            return;
        }

        $progress = progress(label: 'Create Radicle project', steps: 3, hint: 'This may take some time.',);
        $progress->start();

        $this->cloneRadicleProject();
        $progress->advance();

        $this->installPlugins($plugins);
        $progress->advance();

        $this->installDependencies();
        $progress->advance();

        $progress->finish();
        
        
        info('Radicle project created successfully!');
    }

    /**
     * Clone the radicle project
     */
    protected function cloneRadicleProject()
    {
        shell_exec("git clone git@github.com:roots/radicle.git {$this->folder} > /dev/null 2>&1");
    }

    /**
     * Install the plugins
     */
    protected function installPlugins($plugins)
    {
        foreach($plugins as $item){
            $plugin = $this->plugins()[$item];
            if (isset($plugin['repositories'])) {
                foreach ($plugin['repositories'] as $repository) {
                    shell_exec("cd {$this->folder} && composer config repositories.{$plugin['key']} composer {$repository['url']} > /dev/null 2>&1");
                }
            }
            if(isset($plugin['auth'])){
                foreach ($plugin['auth'] as $key => $value) {
                    foreach ($value as $k => $v) {
                        shell_exec("cd {$this->folder} && composer config {$key}.{$k} {$v['username']} {$v['password']} > /dev/null 2>&1");
                    }
                }
            }
            if (isset($plugin['require'])) {
                foreach ($plugin['require'] as $require) {
                    shell_exec("cd {$this->folder} && composer require {$require} > /dev/null 2>&1");
                }
            }
        }
    }

    /**
     * Install the dependencies
     */
    protected function installDependencies()
    {
        // shell_exec("cd {$this->folder} && composer install > /dev/null 2>&1");
        // shell_exec("cd {$this->folder} && npm install > /dev/null 2>&1");
        // shell_exec("cd {$this->folder} && npm run build > /dev/null 2>&1");
    }
}
