<?php

namespace App\Commands\Traits;

use Illuminate\Support\Facades\Config;
use RuntimeException;

use function Laravel\Prompts\password;

/**
 * What a project needs to be built on a golden image of Outlawz Cloud (outlawz-team/cloud-images),
 * and its repository on GitHub. Uses the traits Files and Shell.
 */
trait Cloud
{
    /**
     * The workflow .github/workflows/image.yml (Outlawz Cloud looks for that name), and without the
     * example workflows of Roots. Returns the workflows it removed.
     */
    public function cloudWorkflow($directory, $stack)
    {
        $removed = $this->removeExampleWorkflows($directory);
        $this->writeCloudWorkflow($directory, $stack);

        return $removed;
    }

    /**
     * Remove the example workflows of Roots. Returns the workflows it removed.
     */
    public function removeExampleWorkflows($directory)
    {
        $removed = $this->exampleWorkflows($directory);
        foreach ($removed as $workflow) {
            $this->deleteFile("{$directory}/{$workflow}");
        }

        return $removed;
    }

    /**
     * The workflow .github/workflows/image.yml on the golden image of $stack.
     */
    public function writeCloudWorkflow($directory, $stack)
    {
        if (!is_dir("{$directory}/.github/workflows")) {
            mkdir("{$directory}/.github/workflows", 0755, true);
        }
        $this->stub('cloud/image', [
            'STACK' => $stack,
            'REF' => Config::get("cloud.stacks.{$stack}.ref"),
        ], "{$directory}/.github/workflows/image.yml");
    }

    /**
     * The example workflows of Roots in the project, as .github/workflows/<file>.
     */
    public function exampleWorkflows($directory)
    {
        $examples = [];
        foreach (Config::get('cloud.example_workflows') as $file => $line) {
            $path = "{$directory}/.github/workflows/{$file}";
            if (is_file($path) && str_contains(file_get_contents($path), $line)) {
                $examples[] = ".github/workflows/{$file}";
            }
        }

        return $examples;
    }

    /**
     * Whether image.yml already builds on the golden image.
     */
    public function hasCloudWorkflow($directory)
    {
        $path = "{$directory}/.github/workflows/image.yml";

        return is_file($path) && str_contains(file_get_contents($path), 'outlawz-team/cloud-images/.github/workflows/');
    }

    /**
     * The PHP version the golden image builds a composer.json on: the lowest minor version the
     * requirement allows (>=8.4, ^8.4, ~8.4, ~8.4.0 and 8.4.* are all 8.4). Null when the golden image
     * refuses the requirement (none, a fixed version, alternatives) or has no runtime image for it.
     */
    public function cloudPhpVersion($composerJson, $stack)
    {
        $composer = json_decode(file_get_contents($composerJson), true) ?: [];
        $requirement = str_replace(' ', '', $composer['require']['php'] ?? '');

        if (preg_match('/^(>=|\^|~)(\d+\.\d+)(\.\d+)?$/', $requirement, $match)) {
            $version = $match[2];
        } elseif (preg_match('/^(\d+\.\d+)\.\*$/', $requirement, $match)) {
            $version = $match[1];
        } else {
            return null;
        }

        return in_array($version, Config::get("cloud.stacks.{$stack}.php")) ? $version : null;
    }

    /**
     * Set the PHP requirement of the composer.json in $directory, and the lock file with it.
     */
    public function setPhpRequirement($directory, $requirement)
    {
        $this->shell(['composer', 'require', "php:{$requirement}", '--no-update'], $directory);
        if (is_file("{$directory}/composer.lock")) {
            $this->shell(['composer', 'update', '--lock', '--no-install', '--no-scripts', '--no-interaction'], $directory);
        }
    }

    /**
     * The blocks for DB_SSL (the MySQL user of a site requires TLS) and WP Mail SMTP (the WPMS_*
     * variables of the Mail service) in the WordPress configuration, each where it belongs. Returns
     * what could not be added.
     */
    public function wordpressCloudConfig($config)
    {
        $todo = [];
        $name = basename(dirname($config)) . '/' . basename($config);

        if (!$this->insertStub('cloud/db-ssl', $config, 'DB_SSL', ["Config::define('DB_NAME'"])) {
            $todo[] = "{$name} has no DB_SSL block: add stubs/cloud/db-ssl.stub of the cli by hand.";
        }
        if (!$this->insertStub('cloud/wp-mail-smtp', $config, 'WPMS_ON', ["/**\n * Debugging Settings", '$env_config', 'Config::apply();'])) {
            $todo[] = "{$name} has no WP Mail SMTP block: add stubs/cloud/wp-mail-smtp.stub of the cli by hand.";
        }

        return $todo;
    }

    /**
     * output: "standalone" in next.config of a dashboard: the golden image of the platform runs the
     * standalone server and the build fails without it. Returns false when it cannot add it.
     */
    public function nextStandalone($directory)
    {
        $config = collect(['ts', 'mjs', 'js'])->map(fn ($extension) => "{$directory}/next.config.{$extension}")->first(fn ($path) => is_file($path));
        if ($config === null) {
            return false;
        }

        $contents = file_get_contents($config);
        if (preg_match('/output:\s*["\']standalone["\']/', $contents)) {
            return true;
        }

        $changed = preg_replace('/(const nextConfig(?:\s*:\s*NextConfig)?\s*=\s*\{)\s*\}/', "\$1\n  output: \"standalone\",\n}", $contents, 1, $empty);
        if ($empty === 0) {
            $changed = preg_replace('/(const nextConfig(?:\s*:\s*NextConfig)?\s*=\s*\{)/', "\$1\n  output: \"standalone\",", $contents, 1, $filled);
            if ($filled === 0) {
                return false;
            }
        }
        file_put_contents($config, $changed);

        return true;
    }

    /**
     * The private package sources in a composer.json (ACF Pro, private repositories): the build needs
     * the secret COMPOSER_AUTH for them.
     */
    public function privatePackages($composerJson)
    {
        $composer = json_decode(file_get_contents($composerJson), true) ?: [];

        return collect($composer['repositories'] ?? [])
            ->filter(fn ($source) => is_array($source) && (str_contains($source['url'] ?? '', 'advancedcustomfields.com') || in_array($source['type'] ?? '', ['vcs', 'github', 'git'])))
            ->map(fn ($source) => $source['url'] ?? $source['type'])
            ->values()
            ->all();
    }

    /**
     * The GitHub repository (owner/name) of the remote origin, or null.
     */
    public function githubRepositoryOf($directory)
    {
        try {
            $url = $this->shell(['git', 'remote', 'get-url', 'origin'], $directory, quiet: true);
        } catch (RuntimeException $exception) {
            return null;
        }

        return preg_match('#github\.com[:/]([^/]+/[^/]+?)(\.git)?$#', $url, $match) ? $match[1] : null;
    }

    /**
     * Create the repository on GitHub, set GHCR_PULL_TOKEN (and COMPOSER_AUTH when the build needs
     * it), and push master and develop: the push builds the first images, so the secrets go first.
     * Returns what still needs doing by hand.
     */
    public function createGithubRepository($directory, $repository, $composerAuth)
    {
        $todo = [];

        if ($this->succeeds(['gh', 'repo', 'view', $repository], $directory)) {
            throw new RuntimeException("{$repository} already exists on GitHub: add it as origin and push by hand.");
        }

        $this->shell(['gh', 'repo', 'create', $repository, '--private'], $directory);
        $this->shell(['git', 'remote', 'add', 'origin', "git@github.com:{$repository}.git"], $directory);

        $todo = array_merge($todo, $this->setGhcrPullToken($directory, $repository));
        if ($composerAuth) {
            $todo = array_merge($todo, $this->setComposerAuth($directory, $repository));
        }

        $this->shell(['git', 'push', '-u', 'origin', 'master', 'develop'], $directory);
        $this->shell(['gh', 'repo', 'edit', $repository, '--default-branch', 'develop'], $directory);

        return $todo;
    }

    /**
     * The secret COMPOSER_AUTH of the repository, from the global auth.json of composer. Returns what
     * still needs doing by hand.
     */
    public function setComposerAuth($directory, $repository)
    {
        $auth = $this->shell(['composer', 'config', '--global', 'home'], $directory, quiet: true) . '/auth.json';

        if (!is_file($auth)) {
            return ["The build needs the secret COMPOSER_AUTH and there is no {$auth}: gh secret set COMPOSER_AUTH -R {$repository} < auth.json"];
        }

        $this->shell(['gh', 'secret', 'set', 'COMPOSER_AUTH', '-R', $repository], $directory, input: file_get_contents($auth));

        return [];
    }

    /**
     * The secret GHCR_PULL_TOKEN of the repository: the golden images are private, and on GitHub Free
     * a private repository cannot read the secrets of the organization, so without it the build stops
     * with 401 Unauthorized. The token (ghcr-pull-token-actions in the vault) comes from
     * OUTLAWZ_GHCR_PULL_TOKEN, or is asked for; it goes to gh on stdin, never on the command line.
     * Returns what still needs doing by hand.
     */
    public function setGhcrPullToken($directory, $repository)
    {
        $token = trim((string) getenv('OUTLAWZ_GHCR_PULL_TOKEN'));

        if ($token === '' && isset($this->input) && $this->input->isInteractive()) {
            $token = trim(password(
                label: "GHCR_PULL_TOKEN for {$repository}",
                hint: 'ghcr-pull-token-actions in the vault (read:packages); empty skips it. Or set OUTLAWZ_GHCR_PULL_TOKEN.',
            ));
        }

        if ($token === '') {
            return ["The build needs the secret GHCR_PULL_TOKEN (ghcr-pull-token-actions in the vault): gh secret set GHCR_PULL_TOKEN -R {$repository}"];
        }

        $this->shell(['gh', 'secret', 'set', 'GHCR_PULL_TOKEN', '-R', $repository], $directory, input: $token);

        return [];
    }

    /**
     * The names of the secrets of a repository, or null when gh cannot read them.
     */
    public function repositorySecrets($directory, $repository)
    {
        if ($repository === null) {
            return null;
        }

        try {
            $list = $this->shell(['gh', 'secret', 'list', '-R', $repository], $directory, quiet: true);
        } catch (RuntimeException $exception) {
            return null;
        }

        return collect(explode("\n", $list))->map(fn ($line) => strtok($line, " \t"))->filter()->values()->all();
    }
}
