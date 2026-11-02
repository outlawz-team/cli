<?php

namespace App\Commands;

use App\Commands\Traits\Cloud;
use App\Commands\Traits\Files;
use App\Commands\Traits\Shell;
use Illuminate\Support\Facades\Config;
use LaravelZero\Framework\Commands\Command;
use RuntimeException;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\warning;

class CloudInit extends Command
{
    use Cloud, Files, Shell;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'cloud:init
        {path? : The project, the current folder when empty}
        {--yes : Change everything without asking}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Make an existing Radicle, Bedrock or platform project build on the golden image of Outlawz Cloud.';

    /**
     * Directory
     */
    protected $directory;

    /**
     * What changed
     */
    protected $done = [];

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
        $this->directory = realpath($this->argument('path') ?? getcwd());
        if ($this->directory === false || !is_dir($this->directory)) {
            error('That folder does not exist.');
            return self::FAILURE;
        }

        try {
            $layout = $this->layout();
        } catch (RuntimeException $exception) {
            error($exception->getMessage());
            return self::FAILURE;
        }
        $stack = $layout === 'platform' ? 'platform' : 'wordpress';
        info("{$layout} in {$this->directory}");

        if (!$this->step('Outlawz Cloud', function () use ($layout, $stack) {
            $this->ownImage();
            $this->workflow($stack);
            $this->phpRequirement($layout === 'platform' ? "{$this->directory}/apps/api" : $this->directory, $stack);

            if ($layout === 'platform') {
                $this->dashboard();
            } else {
                $this->wordpressConfig($layout === 'radicle' ? 'bedrock/application.php' : 'config/application.php');
                $this->mailPlugin();
                $this->bud();
            }

            $this->ghcrPullToken();
            $this->composerAuth($layout === 'platform' ? "{$this->directory}/apps/api/composer.json" : "{$this->directory}/composer.json");
        })) {
            return self::FAILURE;
        }

        foreach ($this->done as $done) {
            info("✔ {$done}");
        }
        foreach ($this->todo as $todo) {
            warning($todo);
        }
        info('----------------------------------------');
        info('Next: commit and push to develop; image.yml builds the image on the golden image.');
        info('A trial build first? Run the workflow Image by hand on another branch: it builds and checks the image, but does not push it.');

        return self::SUCCESS;
    }

    /**
     * radicle, bedrock or platform; otherwise why the golden image does not fit.
     */
    protected function layout()
    {
        $file = fn ($path) => is_file("{$this->directory}/{$path}");
        $directory = fn ($path) => is_dir("{$this->directory}/{$path}");

        return match (true) {
            $file('apps/api/artisan') => 'platform',
            $file('bedrock/application.php') && $directory('public') => 'radicle',
            $file('config/application.php') && $directory('public/content') => 'bedrock',
            $file('config/application.php') && $directory('web/app') => throw new RuntimeException('This is Bedrock with web/app; the golden image expects public/content (public/wp and public/content, like Radicle). Convert it first: git mv web public && git mv public/app public/content, then the paths in composer.json, config/application.php and .gitignore.'),
            $file('artisan') && $directory('modules/system') => throw new RuntimeException('This is Winter CMS: there is no golden image for it yet.'),
            default => throw new RuntimeException('No Radicle, Bedrock or platform (apps/api) here: run it in the root of the project.'),
        };
    }

    /**
     * The own Dockerfile and docker/ of the old setup: the golden image replaces them.
     */
    protected function ownImage()
    {
        $own = array_values(array_filter(['Dockerfile', 'docker'], fn ($path) => file_exists("{$this->directory}/{$path}")));
        if ($own === []) {
            return;
        }

        if (!$this->allowed('Remove ' . implode(' and ', $own) . '? The golden image replaces them.')) {
            $this->todo[] = implode(' and ', $own) . ' still there: image.yml does not use them; remove them once the golden image builds the project.';
            return;
        }

        foreach ($own as $path) {
            is_dir("{$this->directory}/{$path}") ? $this->deleteDirectory("{$this->directory}/{$path}") : $this->deleteFile("{$this->directory}/{$path}");
        }
        $this->done[] = 'Removed ' . implode(' and ', $own);
        $this->todo[] = 'Check in git (git diff HEAD -- ' . implode(' ', $own) . ') whether they had settings of this project: php.ini goes in cloud/php.ini, nginx in cloud/nginx/*.conf, extensions in cloud/config.yml.';
    }

    /**
     * image.yml on the golden image, without the example workflows of Roots.
     */
    protected function workflow($stack)
    {
        if ($this->hasCloudWorkflow($this->directory)) {
            $this->done[] = '.github/workflows/image.yml already builds on the golden image';
        } elseif (is_file("{$this->directory}/.github/workflows/image.yml") && !$this->allowed('Replace .github/workflows/image.yml by the one of the golden image?')) {
            $this->todo[] = '.github/workflows/image.yml does not build on the golden image: cloud:init --yes replaces it.';
        } else {
            $this->writeCloudWorkflow($this->directory, $stack);
            $this->done[] = '.github/workflows/image.yml on ' . Config::get("cloud.stacks.{$stack}.ref");
        }

        $examples = $this->exampleWorkflows($this->directory);
        if ($examples === []) {
            return;
        }
        if ($this->allowed('Remove the example workflows of Roots (' . implode(', ', array_map('basename', $examples)) . ')? Outlawz Cloud only needs image.yml.')) {
            $this->done[] = 'Removed ' . implode(', ', $this->removeExampleWorkflows($this->directory));
        } else {
            $this->todo[] = 'Example workflows of Roots: ' . implode(', ', $examples) . '; Outlawz Cloud only needs image.yml.';
        }
    }

    /**
     * A PHP requirement the golden image accepts and has a runtime image for.
     */
    protected function phpRequirement($directory, $stack)
    {
        $composerJson = "{$directory}/composer.json";
        if ($this->cloudPhpVersion($composerJson, $stack) !== null) {
            return;
        }

        $current = json_decode(file_get_contents($composerJson), true)['require']['php'] ?? 'none';
        $options = array_map(fn ($version) => ($stack === 'platform' ? '^' : '>=') . $version, array_reverse(Config::get("cloud.stacks.{$stack}.php")));
        $requirement = match (true) {
            (bool) $this->option('yes') => Config::get("cloud.stacks.{$stack}.requirement"),
            !$this->input->isInteractive() => null,
            default => select("The PHP requirement in composer.json is {$current}; the golden image needs one of its versions. Which one?", [...$options, 'Leave it']),
        };

        if ($requirement === null || $requirement === 'Leave it') {
            $this->todo[] = "PHP requirement {$current} in composer.json: the build fails on it. Set for example \"php\": \"{$options[0]}\", then composer update --lock.";
            return;
        }

        $this->setPhpRequirement($directory, $requirement);
        $this->done[] = "PHP requirement {$requirement} (was {$current})";
    }

    /**
     * The blocks for DB_SSL and WP Mail SMTP in the WordPress configuration.
     */
    protected function wordpressConfig($config)
    {
        $path = "{$this->directory}/{$config}";
        $contents = file_get_contents($path);
        if (str_contains($contents, 'DB_SSL') && str_contains($contents, 'WPMS_ON')) {
            return;
        }

        if (!$this->allowed("Add the blocks for DB_SSL and WP Mail SMTP to {$config}?")) {
            $this->todo[] = "{$config} needs the blocks for DB_SSL and WP Mail SMTP: cloud:init --yes adds them.";
            return;
        }

        $todo = $this->wordpressCloudConfig($path);
        $this->todo = array_merge($this->todo, $todo);
        if ($todo === []) {
            $this->done[] = "{$config}: DB_SSL and WP Mail SMTP";
        }
    }

    /**
     * WP Mail SMTP itself: without it the WPMS_* variables do nothing.
     */
    protected function mailPlugin()
    {
        $composer = json_decode(file_get_contents("{$this->directory}/composer.json"), true) ?: [];
        if (collect(array_keys($composer['require'] ?? []))->contains(fn ($name) => str_ends_with($name, '/wp-mail-smtp'))) {
            return;
        }

        $sources = json_encode($composer['repositories'] ?? []);
        $package = str_contains($sources, 'wp-packages') ? 'wp-plugin/wp-mail-smtp' : 'wpackagist-plugin/wp-mail-smtp';
        $this->todo[] = "WP Mail SMTP is not installed: without it the Mail service of Outlawz Cloud does nothing. composer require {$package}";
    }

    /**
     * A Radicle that builds with bud: the golden image runs "<pm> run build", so cloud/config.yml
     * gives the command of bud.
     */
    protected function bud()
    {
        $package = "{$this->directory}/package.json";
        if (!is_file($package) || !str_contains(file_get_contents($package), '@roots/bud')) {
            return;
        }

        if (is_file("{$this->directory}/cloud/config.yml")) {
            if (!str_contains(file_get_contents("{$this->directory}/cloud/config.yml"), 'assets')) {
                $this->todo[] = 'This project builds with bud: add the assets of stubs/cloud/config-bud.stub of the cli to cloud/config.yml.';
            }
            return;
        }

        if (!is_dir("{$this->directory}/cloud")) {
            mkdir("{$this->directory}/cloud", 0755, true);
        }
        $this->stub('cloud/config-bud', [], "{$this->directory}/cloud/config.yml");
        $this->done[] = 'cloud/config.yml: assets with bud';
    }

    /**
     * output: "standalone" in next.config of the dashboard.
     */
    protected function dashboard()
    {
        if (!is_dir("{$this->directory}/apps/dashboard")) {
            return;
        }

        if (!$this->nextStandalone("{$this->directory}/apps/dashboard")) {
            $this->todo[] = 'apps/dashboard/next.config: add output: "standalone"; without it the build fails.';
        }
    }

    /**
     * The secret COMPOSER_AUTH, when the build needs it for private packages.
     */
    protected function composerAuth($composerJson)
    {
        $private = $this->privatePackages($composerJson);
        if ($private === []) {
            return;
        }

        $repository = $this->githubRepositoryOf($this->directory);
        $secrets = $this->repositorySecrets($this->directory, $repository);

        if ($secrets !== null) {
            if (in_array('COMPOSER_AUTH', $secrets)) {
                return;
            }
            if ($this->allowed("Private packages (" . implode(', ', $private) . "): set the secret COMPOSER_AUTH of {$repository} from your auth.json?")) {
                $todo = $this->setComposerAuth($this->directory, $repository);
                $this->todo = array_merge($this->todo, $todo);
                if ($todo === []) {
                    $this->done[] = "Secret COMPOSER_AUTH of {$repository}";
                }
                return;
            }
        }

        $this->todo[] = 'Private packages (' . implode(', ', $private) . '): the build needs the repository secret COMPOSER_AUTH. gh secret set COMPOSER_AUTH -R ' . ($repository ?? 'outlawz-team/<repo>') . ' < ~/.composer/auth.json';
    }

    /**
     * The secret GHCR_PULL_TOKEN: every build reads the private golden image with it.
     */
    protected function ghcrPullToken()
    {
        $repository = $this->githubRepositoryOf($this->directory);
        $secrets = $this->repositorySecrets($this->directory, $repository);

        if ($secrets !== null) {
            if (in_array('GHCR_PULL_TOKEN', $secrets)) {
                return;
            }
            if ($this->allowed("The build of {$repository} needs the secret GHCR_PULL_TOKEN to read the golden image. Set it?")) {
                $todo = $this->setGhcrPullToken($this->directory, $repository);
                $this->todo = array_merge($this->todo, $todo);
                if ($todo === []) {
                    $this->done[] = "Secret GHCR_PULL_TOKEN of {$repository}";
                }
                return;
            }
        }

        $this->todo[] = 'The build needs the repository secret GHCR_PULL_TOKEN (ghcr-pull-token-actions in the vault) to read the golden image: gh secret set GHCR_PULL_TOKEN -R ' . ($repository ?? 'outlawz-team/<repo>');
    }

    /**
     * Whether a change may go ahead: --yes, otherwise ask (not when there is no one to ask).
     */
    protected function allowed($question)
    {
        return match (true) {
            (bool) $this->option('yes') => true,
            !$this->input->isInteractive() => false,
            default => confirm($question),
        };
    }
}
