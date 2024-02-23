<?php

namespace App\Commands;

use App\Commands\Traits\Stub;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\text;

class CreateRadicle extends Command
{
    use Stub;

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
     * Directory
     */
    protected $directory;

    /**
     * Data
     */
    protected $data = [];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        info('Creating a new Radicle project...');

        $this->folder = $this->argument('folder');

        $this->data['name'] = text(label: 'Name of the project', required: true);
        $this->data['db_host'] = text(label: 'Database host', required: true, default: '127.0.0.1');
        $this->data['db_name'] = text(label: 'Database name', required: true, default: $this->folder);
        $this->data['db_user'] = text(label: 'Database user', required: true, default: 'root');
        $this->data['db_password'] = text(label: 'Database password');
        $this->data['url'] = text(label: 'URL', required: true, default: "{$this->folder}.test");
        
        $this->data['plugins'] = multiselect(
            label: 'Which plugins would you like to include?',
            options: array_column($this->plugins(), 'name', 'key'),
            default: $this->defaultPlugins
        );

        if(!confirm('Are you sure you want to continue?')){
            error('Radicle project creation cancelled.');
            return;
        }

        Config::set("database.connections.terminal", [
            "driver" => "mysql",
            "host" => $this->data['db_host'],
            "database" => 'INFORMATION_SCHEMA',
            "username" => $this->data['db_user'],
            "password" => $this->data['db_password']
        ]);

        try {
            DB::connection('terminal')->getPdo();
        } catch (\Exception $e) {
            error('Could not connect to the database. Please check your credentials and try again.');
            return;
        }

        $database = DB::connection('terminal')->select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$this->data['db_name']}'");
        if (count($database) > 0) {
            if(confirm('Database already exists. Do you want to drop the database?')){
                DB::connection('terminal')->statement("DROP DATABASE {$this->data['db_name']}");
            } else {
                error('Database already exists. Please remove the database or choose a different name.');
                return;
            }
        }

        DB::connection('terminal')->statement("CREATE DATABASE {$this->data['db_name']}");

        info('----------------------------------------');
        info('Clone radicle project');
        info('----------------------------------------');
        $this->cloneRadicleProject();
        info('----------------------------------------');
        info('Installing plugins');
        info('----------------------------------------');
        $this->installingPlugins();
        info('----------------------------------------');
        info('Install dependencies');
        info('----------------------------------------');
        $this->installingDependencies();
        info('----------------------------------------');
        info('Changing files');
        info('----------------------------------------');
        $this->changingFiles();
        info('----------------------------------------');
        info('Configuring wordpress');
        info('----------------------------------------');
        $this->configuringWordpress();
        
        info('----------------------------------------');
        info('Radicle project created successfully!');
        info('----------------------------------------');
        info('You can now visit your project at http://'.$this->data['url']);
        info('And login to the admin panel at http://'.$this->data['url'].'/admin');
        info('----------------------------------------');
        info('Login details:');
        info('Username: outlawz');
        info('Password: Welkom01!');
        info('----------------------------------------');

    }

    /**
     * Clone the radicle project
     */
    protected function cloneRadicleProject()
    {
        shell_exec("git clone git@github.com:roots/radicle.git {$this->folder}");
        $this->directory = exec("cd {$this->folder} && pwd");
    }

    /**
     * Clone the radicle project
     */
    protected function changingFiles()
    {
        $this->stub('radicle/style', [
            'PROJECTNAME' => $this->data['name']
        ], "{$this->directory}/public/content/themes/radicle/style.css");
        $this->stub('radicle/screenshot', [], "{$this->directory}/public/content/themes/radicle/screenshot.png");
        $this->stub('radicle/env', [
            'DB_HOST' => $this->data['db_host'],
            'DB_NAME' => $this->data['db_name'],
            'DB_USER' => $this->data['db_user'],
            'DB_PASSWORD' => $this->data['db_password'],
            'WP_HOME' => "http://{$this->data['url']}",
        ], "{$this->directory}/.env");
    }

    /**
     * Install the plugins
     */
    protected function installingPlugins()
    {
        foreach($this->data['plugins'] as $item){
            $plugin = $this->plugins()[$item];
            if (isset($plugin['repositories'])) {
                foreach ($plugin['repositories'] as $repository) {
                    shell_exec("cd {$this->folder} && composer config repositories.{$plugin['key']} composer {$repository['url']}");
                }
            }
            if (isset($plugin['require'])) {
                foreach ($plugin['require'] as $require) {
                    shell_exec("cd {$this->folder} && composer require {$require}");
                }
            }
        }
    }

    /**
     * Install the dependencies
     */
    protected function installingDependencies()
    {
        shell_exec("cd {$this->folder} && composer install");
        shell_exec("cd {$this->folder} && npm install");
        shell_exec("cd {$this->folder} && npm run build");
    }

    /**
     * Install wordpress
     */
    protected function configuringWordpress()
    {
        shell_exec("cd {$this->folder} && wp core install --url={$this->data['url']} --title={$this->data['name']} --admin_user=outlawz --admin_password=Welkom01! --admin_email=dev@outlawz.nl");
        shell_exec("cd {$this->folder} && wp post delete $(wp post list --post_type='page' --format=ids) --force");
        shell_exec("cd {$this->folder} && wp post delete $(wp post list --post_type='post' --format=ids) --force");
        shell_exec("cd {$this->folder} && wp option update show_on_front page && wp option update page_on_front $(wp post create --post_type=page --post_title='Home' --post_status='publish' --porcelain)");
        shell_exec("cd {$this->folder} && wp rewrite structure '/%postname%/'");
        shell_exec("cd {$this->folder} && wp option update timezone_string 'Europe/Amsterdam'");
        shell_exec("cd {$this->folder} && wp language core install nl_NL && wp site switch-language nl_NL");
    }
}
