<?php

namespace App\Commands;

use App\Commands\Traits\Delete;
use App\Commands\Traits\Files;
use App\Commands\Traits\Stub;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class CreateRadicle extends Command
{
    use Files;

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
     * Composer packages
     */
    protected $composer = [
        'outlawz-team/radicle',
    ];

    /**
     * Npm packages
     */
    protected $npm = [
        'dev' => [
            '@prettier/plugin-php',
            'prettier-plugin-tailwindcss',
            '@shufo/prettier-plugin-blade',
            'lint-staged',
            'husky'
        ]
    ];

    /**
     * Plugins
     */
    protected function plugins()
    {
        return Config::get('wordpress.plugins');
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
     * What still needs doing by hand for Outlawz Cloud
     */
    protected $cloudTodo = [];

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
            default: config('wordpress.default_plugins')
        );

        if (!confirm('Are you sure you want to continue?')) {
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
            if (confirm('Database already exists. Do you want to drop the database?')) {
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
        info('Git');
        info('----------------------------------------');
        $this->setGit();
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
        info('Configuring Outlawz Cloud');
        info('----------------------------------------');
        $this->configuringCloud();
        info('----------------------------------------');
        info('Configuring wordpress');
        info('----------------------------------------');
        $this->configuringWordpress();
        info('----------------------------------------');
        info('Commit changes');
        info('----------------------------------------');
        $this->commitChanges();

        info('----------------------------------------');
        info('Radicle project created successfully!');
        info('----------------------------------------');
        info('You can now visit your project at http://' . $this->data['url']);
        info('And login to the admin panel at http://' . $this->data['url'] . '/admin');
        info('----------------------------------------');
        info('Login details:');
        info('Username: outlawz');
        info('Password: Welkom01!');
        info('----------------------------------------');
        info('Outlawz Cloud:');
        info('Push develop and master to a repository in outlawz-team: .github/workflows/image.yml builds the image on the golden image.');
        if (collect($this->data['plugins'])->contains(fn ($item) => isset($this->plugins()[$item]['repositories']))) {
            warning('Private packages (ACF Pro): the build needs the repository secret COMPOSER_AUTH with the content of auth.json.');
            warning('  gh secret set COMPOSER_AUTH -R outlawz-team/<repo> < ~/.composer/auth.json');
        }
        foreach ($this->cloudTodo as $todo) {
            warning($todo);
        }
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
        $this->stub('radicle/lintstagedrc', [], "{$this->directory}/.lintstagedrc");
        $this->stub('radicle/prettierrc', [], "{$this->directory}/.prettierrc");
        $this->deleteDirectory("{$this->directory}/.git");
        $this->emptyDirectory("{$this->directory}/app/Composers");
        $this->stub('radicle/gitkeep', [], "{$this->directory}/app/Composers/.gitkeep");
        $this->emptyDirectory("{$this->directory}/resources/images/icons");
        $this->stub('radicle/gitkeep', [], "{$this->directory}/resources/images/icons/.gitkeep");
        $this->emptyDirectory("{$this->directory}/resources/scripts/editor");
        $this->stub('radicle/gitkeep', [], "{$this->directory}/resources/scripts/editor/.gitkeep");
        $this->deleteDirectory("{$this->directory}/resources/views/blocks");
        $this->deleteFile("{$this->directory}/resources/views/components/alert.blade.php");
        $this->deleteFile("{$this->directory}/resources/views/components/modal.blade.php");
        $this->deleteFile("{$this->directory}/resources/views/components/table.blade.php");
        $this->deleteDirectory("{$this->directory}/resources/views/forms");
        $this->stub('radicle/app', [], "{$this->directory}/resources/views/layouts/app.blade.php");
        $this->deleteDirectory("{$this->directory}/resources/views/partials");
        $this->stub('radicle/header', [], "{$this->directory}/resources/views/sections/header.blade.php");
        $this->stub('radicle/footer', [], "{$this->directory}/resources/views/sections/footer.blade.php");
        $this->stub('radicle/404', [], "{$this->directory}/resources/views/404.blade.php");
        $this->stub('radicle/index', [], "{$this->directory}/resources/views/index.blade.php");
        $this->stub('radicle/page', [], "{$this->directory}/resources/views/page.blade.php");
        $this->stub('radicle/template-custom', [], "{$this->directory}/resources/views/template-custom.blade.php");
        $this->deleteFile("{$this->directory}/resources/views/search.blade.php");
        $this->deleteFile("{$this->directory}/resources/views/single.blade.php");
        $this->deleteFile("{$this->directory}/resources/views/welcome.blade.php");
        $this->stub('radicle/web', [], "{$this->directory}/routes/web.php");
        $this->stub('radicle/BlocksServiceProvider', [], "{$this->directory}/app/Providers/BlocksServiceProvider.php");
        $this->stub('radicle/post-types', [], "{$this->directory}/config/post-types.php");
    }

    /**
     * Make the project ready for Outlawz Cloud: the workflow that builds the image on the golden
     * image (outlawz-team/cloud-images), a PHP requirement the golden image accepts, and the
     * configuration the variables of the platform need (DB_SSL, WP Mail SMTP).
     */
    protected function configuringCloud()
    {
        foreach (Config::get('cloud.example_workflows') as $workflow) {
            $this->deleteFile("{$this->directory}/.github/workflows/{$workflow}");
        }
        if (!is_dir("{$this->directory}/.github/workflows")) {
            mkdir("{$this->directory}/.github/workflows", 0755, true);
        }
        $this->stub('radicle/image', [
            'CLOUD_IMAGES_REF' => Config::get('cloud.cloud_images_ref'),
        ], "{$this->directory}/.github/workflows/image.yml");

        // The golden image takes the lowest minor version the requirement allows: >=8.4, ^8.4, ~8.4 or 8.4.*.
        $composer = json_decode(file_get_contents("{$this->directory}/composer.json"), true);
        $php = str_replace(' ', '', $composer['require']['php'] ?? '');
        if (!preg_match('/^((>=|\^|~)\d+\.\d+(\.\d+)?|\d+\.\d+\.\*)$/', $php)) {
            shell_exec("cd {$this->folder} && composer require 'php:>=8.4'");
        }

        $config = "{$this->directory}/bedrock/application.php";
        if (!$this->insertStub('radicle/db-ssl', $config, 'DB_SSL', ["Config::define('DB_NAME'"])) {
            $this->cloudTodo[] = 'bedrock/application.php has no DB_SSL block: add stubs/radicle/db-ssl.stub of the cli by hand.';
        }
        if (!$this->insertStub('radicle/wp-mail-smtp', $config, 'WPMS_ON', ["/**\n * Debugging Settings", 'Config::apply();'])) {
            $this->cloudTodo[] = 'bedrock/application.php has no WP Mail SMTP block: add stubs/radicle/wp-mail-smtp.stub of the cli by hand.';
        }
        if (!in_array('wp-mail-smtp', $this->data['plugins'])) {
            $this->cloudTodo[] = 'WP Mail SMTP is not installed: without it the Mail service of Outlawz Cloud does nothing.';
        }
    }

    /**
     * Install the plugins
     */
    protected function installingPlugins()
    {
        $wpackagist = escapeshellarg(json_encode([
            'type' => 'composer',
            'url' => 'https://wpackagist.org',
            'only' => ['wpackagist-plugin/*', 'wpackagist-theme/*'],
        ]));
        shell_exec("cd {$this->folder} && composer config repositories.wpackagist {$wpackagist}");

        foreach ($this->data['plugins'] as $item) {
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
        foreach ($this->composer as $package) {
            shell_exec("cd {$this->folder} && composer require {$package}");
        }

        foreach ($this->npm['dev'] as $package) {
            shell_exec("cd {$this->folder} && npm install {$package} --save-dev");
        }

        shell_exec("cd {$this->folder} && composer install");
        shell_exec("cd {$this->folder} && npm install");
        shell_exec("cd {$this->folder} && npm run build");

        shell_exec("cd {$this->folder} && npx husky init");
        $this->stub('radicle/pre-commit', [], "{$this->directory}/.husky/pre-commit");
    }

    /**
     * Set git
     */
    protected function setGit()
    {
        shell_exec("cd {$this->folder} && git branch -m main master");
        shell_exec("cd {$this->folder} && git flow init -d");
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
        shell_exec("cd {$this->folder} && wp acorn outlawz:boost");
    }

    /**
     * Commit changes
     */
    protected function commitChanges()
    {
        shell_exec("cd {$this->folder} && git add .");
        shell_exec("cd {$this->folder} && git commit -m 'Initial commit'");
    }
}
