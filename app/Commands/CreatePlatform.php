<?php

namespace App\Commands;

use App\Commands\Traits\Cloud;
use App\Commands\Traits\Files;
use App\Commands\Traits\Shell;
use Illuminate\Support\Facades\Config;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class CreatePlatform extends Command
{
    use Cloud, Files, Shell;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'create:platform {folder : The folder to create the project in.}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create a new platform project (Laravel API and Next.js dashboard) from platform-boilerplate.';

    /**
     * The repository the project starts from
     */
    protected $boilerplate = 'git@github.com:outlawz-team/platform-boilerplate.git';

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
     * What still needs doing by hand
     */
    protected $todo = [];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        info('Creating a new platform project...');

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
        $this->data['repository'] = Config::get('cloud.organization') . "/{$this->folder}";
        $this->data['github'] = confirm("Create the repository {$this->data['repository']} on GitHub and push it?");

        if (!$this->requireTools(['git', 'git-flow', 'php', 'composer', 'npm', ...($this->data['github'] ? ['gh'] : [])])) {
            return self::FAILURE;
        }

        if (!confirm('Are you sure you want to continue?')) {
            error('Platform project creation cancelled.');
            return self::FAILURE;
        }

        $steps = [
            'Clone platform-boilerplate' => fn () => $this->cloneBoilerplate(),
            'Git' => fn () => $this->setGit(),
            'Naming the project' => fn () => $this->naming(),
            'Configuring Outlawz Cloud' => fn () => $this->configuringCloud(),
            'Install the API' => fn () => $this->installingApi(),
            'Install the dashboard' => fn () => $this->installingDashboard(),
            'Commit changes' => fn () => $this->commitChanges(),
            ...($this->data['github'] ? ['GitHub' => fn () => $this->pushToGithub()] : []),
        ];

        foreach ($steps as $title => $step) {
            if (!$this->step($title, $step)) {
                error("The project in {$this->folder} is not finished: fix the problem above, then remove the folder and try again.");
                return self::FAILURE;
            }
        }

        info('----------------------------------------');
        info('Platform project created successfully!');
        info('----------------------------------------');
        info("Run the API:       cd {$this->folder}/apps/api && composer run dev");
        info("Run the dashboard: cd {$this->folder}/apps/dashboard && npm run dev");
        info('Then log in at http://localhost:3000 with dev@outlawz.nl and Welkom01!');
        info('----------------------------------------');
        info('Outlawz Cloud:');
        info($this->data['github']
            ? "Pushed to https://github.com/{$this->data['repository']}: .github/workflows/image.yml builds the image of develop on the golden image."
            : 'Push develop and master to a repository in outlawz-team: .github/workflows/image.yml builds the image on the golden image.');
        if (!$this->data['github']) {
            warning('Set the repository secret GHCR_PULL_TOKEN (ghcr-pull-token-actions in the vault) before the first push: gh secret set GHCR_PULL_TOKEN -R outlawz-team/<repo>');
        }
        foreach ($this->todo as $todo) {
            warning($todo);
        }
        warning('Still to check: the development user in apps/api/database/seeders/DatabaseSeeder.php (and bruno/environments/local.bru), and SANCTUM_EXPIRATION in apps/api/.env.example.');
        info('----------------------------------------');

        return self::SUCCESS;
    }

    /**
     * Clone the boilerplate
     */
    protected function cloneBoilerplate()
    {
        $this->shell(['git', 'clone', '--depth', '1', $this->boilerplate, $this->folder]);
        $this->directory = realpath($this->folder);
    }

    /**
     * Set git: a history of its own instead of the one of the boilerplate, with git flow (master and develop)
     */
    protected function setGit()
    {
        $this->deleteDirectory("{$this->directory}/.git");
        $this->shell(['git', 'init', '-b', 'master'], $this->directory);
        $this->shell(['git', 'flow', 'init', '-d'], $this->directory);
    }

    /**
     * The names of the checklist of the boilerplate: APP_NAME, the composer package, the npm package
     * and the Bruno collection
     */
    protected function naming()
    {
        $name = str_replace(['"', '\\'], '', $this->data['name']);

        $env = "{$this->directory}/apps/api/.env.example";
        file_put_contents($env, preg_replace('/^APP_NAME=.*$/m', "APP_NAME=\"{$name}\"", file_get_contents($env), 1));

        $this->shell(['composer', 'config', 'name', Config::get('cloud.organization') . "/{$this->folder}-api"], "{$this->directory}/apps/api");
        $this->shell(['composer', 'config', 'description', "The API of {$name}"], "{$this->directory}/apps/api");
        $this->shell(['npm', 'pkg', 'set', "name={$this->folder}-dashboard"], "{$this->directory}/apps/dashboard");

        $bruno = "{$this->directory}/bruno/bruno.json";
        file_put_contents($bruno, preg_replace('/"name":\s*"[^"]*"/', '"name": ' . json_encode($name, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), file_get_contents($bruno), 1));
    }

    /**
     * Make the project ready for Outlawz Cloud: the workflow that builds the image on the golden
     * image of the platform, a PHP version it has a runtime image for, and the standalone server of
     * Next.js that the image runs.
     */
    protected function configuringCloud()
    {
        $this->cloudWorkflow($this->directory, 'platform');

        $api = "{$this->directory}/apps/api";
        if ($this->cloudPhpVersion("{$api}/composer.json", 'platform') === null) {
            $this->setPhpRequirement($api, Config::get('cloud.stacks.platform.requirement'));
        } else {
            // The new name of the package changes the content hash of composer.lock.
            $this->shell(['composer', 'update', '--lock', '--no-install', '--no-scripts', '--no-interaction'], $api);
        }

        if (!$this->nextStandalone("{$this->directory}/apps/dashboard")) {
            $this->todo[] = 'apps/dashboard/next.config: add output: "standalone"; without it the build fails.';
        }
    }

    /**
     * Install the API: dependencies, .env, APP_KEY, the SQLite database with the development user
     */
    protected function installingApi()
    {
        $api = "{$this->directory}/apps/api";
        $this->shell(['composer', 'run', 'setup'], $api);
        $this->shell(['php', 'artisan', 'db:seed', '--force'], $api);
    }

    /**
     * Install the dashboard
     */
    protected function installingDashboard()
    {
        $dashboard = "{$this->directory}/apps/dashboard";
        $this->shell(['npm', 'install'], $dashboard);
        copy("{$dashboard}/.env.example", "{$dashboard}/.env.local");
    }

    /**
     * Commit changes, on develop
     */
    protected function commitChanges()
    {
        $this->shell(['git', 'add', '.'], $this->directory);
        $this->shell(['git', 'commit', '--no-verify', '-m', 'chore: initial commit from platform-boilerplate'], $this->directory);
    }

    /**
     * Create the repository on GitHub, with COMPOSER_AUTH when the build needs it, and push it
     */
    protected function pushToGithub()
    {
        $this->todo = array_merge($this->todo, $this->createGithubRepository(
            $this->directory,
            $this->data['repository'],
            $this->privatePackages("{$this->directory}/apps/api/composer.json") !== []
        ));
    }
}
