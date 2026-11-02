<?php

namespace App\Commands\Traits;

use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

trait Shell
{
    /**
     * Run a command without a shell: every argument goes as it is, so nothing needs escaping.
     * The output is shown as it comes; a command that fails stops the step it is in.
     */
    public function shell(array $command, ?string $cwd = null, bool $quiet = false, ?string $input = null)
    {
        $process = new Process($command, $cwd, timeout: null);
        $process->setInput($input);
        $process->run(function ($type, $output) use ($quiet) {
            if (!$quiet) {
                $this->output?->write($output);
            }
        });

        if (!$process->isSuccessful()) {
            $output = trim($process->getErrorOutput() ?: $process->getOutput());
            throw new RuntimeException(implode(' ', $command) . ' failed' . ($quiet && $output !== '' ? ":\n{$output}" : ''));
        }

        return trim($process->getOutput());
    }

    /**
     * Whether a command succeeds, without showing its output.
     */
    public function succeeds(array $command, ?string $cwd = null)
    {
        return (new Process($command, $cwd))->run() === 0;
    }

    /**
     * Run a step with a header; when it fails, say which step and stop.
     */
    public function step($title, callable $step)
    {
        info('----------------------------------------');
        info($title);
        info('----------------------------------------');

        try {
            $step();

            return true;
        } catch (RuntimeException $exception) {
            error("{$title} failed: {$exception->getMessage()}");

            return false;
        }
    }

    /**
     * The tools that are missing, with how to install them.
     */
    public function missingTools(array $tools)
    {
        $checks = [
            'git' => [['git', '--version'], 'xcode-select --install'],
            'git-flow' => [['git', 'flow', 'version'], 'brew install git-flow'],
            'composer' => [['composer', '--version'], 'brew install composer (or Herd)'],
            'php' => [['php', '--version'], 'Herd'],
            'npm' => [['npm', '--version'], 'brew install node (or Herd)'],
            'wp' => [['wp', '--version'], 'brew install wp-cli'],
            'gh' => [['gh', 'auth', 'status'], 'brew install gh && gh auth login'],
        ];

        $missing = [];
        foreach ($tools as $tool) {
            [$command, $install] = $checks[$tool];
            if ((new ExecutableFinder)->find($command[0]) === null || (new Process($command))->run() !== 0) {
                $missing[$tool] = $install;
            }
        }

        return $missing;
    }

    /**
     * Check the tools a command needs before it starts, so it does not stop halfway.
     */
    public function requireTools(array $tools)
    {
        $missing = $this->missingTools($tools);

        foreach ($missing as $tool => $install) {
            error("{$tool} is not installed or does not work: {$install}");
        }

        return $missing === [];
    }
}
