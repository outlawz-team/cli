<?php

namespace App\Commands;

use App\Commands\Traits\Shell;
use LaravelZero\Framework\Commands\Command;
use RuntimeException;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class SelfUpdate extends Command
{
    use Shell;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'self-update';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Update the cli to its newest version.';

    /**
     * The repository of the cli: private, so not on Packagist. Composer reads it with the GitHub token
     * in its auth.json.
     */
    public const REPOSITORY = 'https://github.com/outlawz-team/cli';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (!$this->requireTools(['composer'])) {
            return self::FAILURE;
        }

        try {
            $this->shell(['composer', 'global', 'config', 'repositories.outlawz-cli', 'vcs', self::REPOSITORY]);
            $this->shell(['composer', 'global', 'update', 'outlawz-team/cli', '--no-interaction', '--with-all-dependencies']);
        } catch (RuntimeException $exception) {
            error($exception->getMessage());
            error('Composer needs a GitHub token that can read outlawz-team/cli: composer config -g github-oauth.github.com "$(gh auth token)"');

            return self::FAILURE;
        }

        info('The cli is up to date.');

        return self::SUCCESS;
    }
}
