<?php

namespace App\Commands;

use App\Commands\Traits\Cloud;
use App\Commands\Traits\Files;
use App\Commands\Traits\Shell;
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
    use Cloud, Files, Shell;

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

        if (!preg_match('/^[A-Za-z0-9._-]+$/', $this->folder)) {
            error('The folder may only contain letters, digits, dots, dashes and underscores.');
            return self::FAILURE;
        }
        if (file_exists($this->folder)) {
            error("{$this->folder} already exists.");
            return self::FAILURE;
        }

        $this->data['name'] = text(label: 'Name of the project', required: true);
        $this->data['db_host'] = text(label: 'Database host', required: true, default: '127.0.0.1');
        $this->data['db_name'] = text(label: 'Database name', required: true, default: str_replace('.', '_', $this->folder), validate: fn ($name) => $this->validDatabaseName($name) ? null : 'Only letters, digits, dashes and underscores.');
        $this->data['db_user'] = text(label: 'Database user', required: true, default: 'root');
        $this->data['db_password'] = text(label: 'Database password');
        $this->data['url'] = text(label: 'URL', required: true, default: "{$this->folder}.test");

        $this->data['plugins'] = multiselect(
            label: 'Which plugins would you like to include?',
            options: array_column($this->plugins(), 'name', 'key'),
            default: config('wordpress.default_plugins')
        );

        $this->data['repository'] = Config::get('cloud.organization') . "/{$this->folder}";
        $this->data['github'] = confirm("Create the repository {$this->data['repository']} on GitHub and push it?");

        if (!$this->requireTools(['git', 'git-flow', 'composer', 'npm', 'wp', ...($this->data['github'] ? ['gh'] : [])])) {
            return self::FAILURE;
        }

        if (!confirm('Are you sure you want to continue?')) {
            error('Radicle project creation cancelled.');
            return self::FAILURE;
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
            return self::FAILURE;
        }

        $database = DB::connection('terminal')->select('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?', [$this->data['db_name']]);
        if (count($database) > 0) {
            if (confirm('Database already exists. Do you want to drop the database?')) {
                DB::connection('terminal')->statement("DROP DATABASE `{$this->data['db_name']}`");
            } else {
                error('Database already exists. Please remove the database or choose a different name.');
                return self::FAILURE;
            }
        }

        DB::connection('terminal')->statement("CREATE DATABASE `{$this->data['db_name']}`");

        $steps = [
            'Clone radicle project' => fn () => $this->cloneRadicleProject(),
            'Git' => fn () => $this->setGit(),
            'Installing plugins' => fn () => $this->installingPlugins(),
            'Install dependencies' => fn () => $this->installingDependencies(),
            'Changing files' => fn () => $this->changingFiles(),
            'Configuring Outlawz Cloud' => fn () => $this->configuringCloud(),
            'Configuring wordpress' => fn () => $this->configuringWordpress(),
            'Commit changes' => fn () => $this->commitChanges(),
            ...($this->data['github'] ? ['GitHub' => fn () => $this->pushToGithub()] : []),
        ];

        foreach ($steps as $title => $step) {
            if (!$this->step($title, $step)) {
                error("The project in {$this->folder} is not finished: fix the problem above, then remove the folder and the database and try again.");
                return self::FAILURE;
            }
        }

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
        if ($this->data['github']) {
            info("Pushed to https://github.com/{$this->data['repository']}: .github/workflows/image.yml builds the image of develop on the golden image.");
        } else {
            info('Push develop and master to a repository in outlawz-team: .github/workflows/image.yml builds the image on the golden image.');
            warning('Set the repository secret GHCR_PULL_TOKEN (ghcr-pull-token-actions in the vault) before the first push: gh secret set GHCR_PULL_TOKEN -R outlawz-team/<repo>');
            if ($this->privatePackages("{$this->directory}/composer.json") !== []) {
                warning('Private packages (ACF Pro): the build needs the repository secret COMPOSER_AUTH with the content of auth.json.');
                warning('  gh secret set COMPOSER_AUTH -R outlawz-team/<repo> < ~/.composer/auth.json');
            }
        }
        foreach ($this->cloudTodo as $todo) {
            warning($todo);
        }
        info('----------------------------------------');

        return self::SUCCESS;
    }

    /**
     * Whether a database name is safe to use between backticks
     */
    protected function validDatabaseName($name)
    {
        return preg_match('/^[A-Za-z0-9_-]{1,64}$/', $name) === 1;
    }

    /**
     * The keys and salts of WordPress: 64 random characters each, without quotes, backslashes or
     * dollar signs, so they fit between the single quotes in .env.
     */
    protected function salts()
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#%^&*()-_[]{}<>~+=,.;:/?|';
        $salts = [];

        foreach (['AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT'] as $key) {
            $salt = '';
            for ($i = 0; $i < 64; $i++) {
                $salt .= $characters[random_int(0, strlen($characters) - 1)];
            }
            $salts[$key] = $salt;
        }

        return $salts;
    }

    /**
     * Clone the radicle project
     */
    protected function cloneRadicleProject()
    {
        $this->shell(['git', 'clone', '--depth', '1', 'git@github.com:roots/radicle.git', $this->folder]);
        $this->directory = realpath($this->folder);
    }

    /**
     * Changing files
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
            ...$this->salts(),
        ], "{$this->directory}/.env");
        $this->stub('radicle/lintstagedrc', [], "{$this->directory}/.lintstagedrc");
        $this->stub('radicle/prettierrc', [], "{$this->directory}/.prettierrc");
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
        $this->cloudWorkflow($this->directory, 'wordpress');

        if ($this->cloudPhpVersion("{$this->directory}/composer.json", 'wordpress') === null) {
            $this->setPhpRequirement($this->directory, Config::get('cloud.stacks.wordpress.requirement'));
        }

        $this->cloudTodo = $this->wordpressCloudConfig("{$this->directory}/bedrock/application.php");
        if (!in_array('wp-mail-smtp', $this->data['plugins'])) {
            $this->cloudTodo[] = 'WP Mail SMTP is not installed: without it the Mail service of Outlawz Cloud does nothing.';
        }
    }

    /**
     * Install the plugins
     */
    protected function installingPlugins()
    {
        $wpackagist = json_encode([
            'type' => 'composer',
            'url' => 'https://wpackagist.org',
            'only' => ['wpackagist-plugin/*', 'wpackagist-theme/*'],
        ]);
        $this->shell(['composer', 'config', 'repositories.wpackagist', $wpackagist], $this->directory);

        foreach ($this->data['plugins'] as $item) {
            $plugin = $this->plugins()[$item];
            foreach ($plugin['repositories'] ?? [] as $repository) {
                $this->shell(['composer', 'config', "repositories.{$plugin['key']}", $repository['type'], $repository['url']], $this->directory);
            }
            if (isset($plugin['require'])) {
                $this->shell(['composer', 'require', ...$plugin['require']], $this->directory);
            }
        }
    }

    /**
     * Install the dependencies
     */
    protected function installingDependencies()
    {
        $this->shell(['composer', 'require', ...$this->composer], $this->directory);
        $this->shell(['npm', 'install', '--save-dev', ...$this->npm['dev']], $this->directory);

        $this->shell(['composer', 'install'], $this->directory);
        $this->shell(['npm', 'install'], $this->directory);
        $this->shell(['npm', 'run', 'build'], $this->directory);

        $this->shell(['npx', 'husky', 'init'], $this->directory);
        $this->stub('radicle/pre-commit', [], "{$this->directory}/.husky/pre-commit");
    }

    /**
     * Set git: a history of its own instead of the one of Roots, with git flow (master and develop)
     */
    protected function setGit()
    {
        $this->deleteDirectory("{$this->directory}/.git");
        $this->shell(['git', 'init', '-b', 'master'], $this->directory);
        $this->shell(['git', 'flow', 'init', '-d'], $this->directory);
    }

    /**
     * Install wordpress
     */
    protected function configuringWordpress()
    {
        $this->shell(['wp', 'core', 'install', "--url={$this->data['url']}", "--title={$this->data['name']}", '--admin_user=outlawz', '--admin_password=Welkom01!', '--admin_email=dev@outlawz.nl'], $this->directory);

        foreach (['page', 'post'] as $type) {
            $ids = $this->shell(['wp', 'post', 'list', "--post_type={$type}", '--format=ids'], $this->directory, quiet: true);
            if ($ids !== '') {
                $this->shell(['wp', 'post', 'delete', ...explode(' ', $ids), '--force'], $this->directory);
            }
        }

        $home = $this->shell(['wp', 'post', 'create', '--post_type=page', '--post_title=Home', '--post_status=publish', '--porcelain'], $this->directory, quiet: true);
        $this->shell(['wp', 'option', 'update', 'show_on_front', 'page'], $this->directory);
        $this->shell(['wp', 'option', 'update', 'page_on_front', $home], $this->directory);
        $this->shell(['wp', 'rewrite', 'structure', '/%postname%/'], $this->directory);
        $this->shell(['wp', 'option', 'update', 'timezone_string', 'Europe/Amsterdam'], $this->directory);
        $this->shell(['wp', 'language', 'core', 'install', 'nl_NL'], $this->directory);
        $this->shell(['wp', 'site', 'switch-language', 'nl_NL'], $this->directory);
        $this->shell(['wp', 'acorn', 'outlawz:boost'], $this->directory);
    }

    /**
     * Commit changes, on develop. Without the pre-commit hook: lint-staged on every file of the
     * project is not what it is for.
     */
    protected function commitChanges()
    {
        $this->shell(['git', 'add', '.'], $this->directory);
        $this->shell(['git', 'commit', '--no-verify', '-m', 'Initial commit'], $this->directory);
    }

    /**
     * Create the repository on GitHub, with COMPOSER_AUTH when the build needs it, and push it
     */
    protected function pushToGithub()
    {
        $this->cloudTodo = array_merge($this->cloudTodo, $this->createGithubRepository(
            $this->directory,
            $this->data['repository'],
            $this->privatePackages("{$this->directory}/composer.json") !== []
        ));
    }
}
