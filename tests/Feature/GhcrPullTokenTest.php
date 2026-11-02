<?php

use App\Commands\Traits\Cloud;
use App\Commands\Traits\Files;
use App\Commands\Traits\Shell;

/**
 * A gh in PATH that lists $secrets and writes every call (arguments and stdin) to the returned log.
 */
function fakeGh(array $secrets = []): string
{
    $bin = project();
    $log = "{$bin}/calls.log";
    file_put_contents("{$bin}/gh", '#!/usr/bin/env php' . "\n" . '<?php' . "\n"
        . '$args = array_slice($argv, 1);' . "\n"
        . 'if (array_slice($args, 0, 2) === ["secret", "list"]) { foreach (' . var_export($secrets, true) . ' as $s) echo "$s\t2026-11-02\n"; exit(0); }' . "\n"
        . 'file_put_contents(' . var_export($log, true) . ', json_encode(["args" => $args, "stdin" => stream_get_contents(STDIN)]) . "\n", FILE_APPEND);' . "\n");
    chmod("{$bin}/gh", 0755);
    putenv("PATH={$bin}:" . getenv('PATH'));

    return $log;
}

function ghCalls(string $log): array
{
    return is_file($log) ? array_map(fn ($line) => json_decode($line, true), file($log, FILE_IGNORE_NEW_LINES)) : [];
}

function withRemote(string $directory, string $repository): string
{
    exec('git -C ' . escapeshellarg($directory) . ' init -q && git -C ' . escapeshellarg($directory) . ' remote add origin ' . escapeshellarg("git@github.com:{$repository}.git"));

    return $directory;
}

beforeEach(function () {
    $this->path = getenv('PATH');
    putenv('OUTLAWZ_GHCR_PULL_TOKEN');
});

afterEach(function () {
    putenv("PATH={$this->path}");
    putenv('OUTLAWZ_GHCR_PULL_TOKEN');
});

it('sets GHCR_PULL_TOKEN from OUTLAWZ_GHCR_PULL_TOKEN, on stdin', function () {
    $log = fakeGh();
    putenv('OUTLAWZ_GHCR_PULL_TOKEN=ghp_secret');
    $cloud = new class {
        use Cloud, Files, Shell;
    };

    expect($cloud->setGhcrPullToken(project(), 'outlawz-team/site'))->toBe([])
        ->and(ghCalls($log))->toBe([['args' => ['secret', 'set', 'GHCR_PULL_TOKEN', '-R', 'outlawz-team/site'], 'stdin' => 'ghp_secret']]);
});

it('says how to set GHCR_PULL_TOKEN when there is no token and no one to ask', function () {
    $log = fakeGh();
    $cloud = new class {
        use Cloud, Files, Shell;
    };

    expect($cloud->setGhcrPullToken(project(), 'outlawz-team/site'))
        ->toBe(['The build needs the secret GHCR_PULL_TOKEN (ghcr-pull-token-actions in the vault): gh secret set GHCR_PULL_TOKEN -R outlawz-team/site'])
        ->and(ghCalls($log))->toBe([]);
});

it('lets cloud:init set GHCR_PULL_TOKEN when the repository has none', function () {
    $log = fakeGh(['COMPOSER_AUTH']);
    putenv('OUTLAWZ_GHCR_PULL_TOKEN=ghp_secret');
    $directory = withRemote(radicle(), 'outlawz-team/site');

    $this->artisan('cloud:init', ['path' => $directory, '--yes' => true])
        ->expectsOutputToContain('Secret GHCR_PULL_TOKEN of outlawz-team/site')
        ->assertExitCode(0);

    expect(ghCalls($log))->toBe([['args' => ['secret', 'set', 'GHCR_PULL_TOKEN', '-R', 'outlawz-team/site'], 'stdin' => 'ghp_secret']]);
});

it('leaves a GHCR_PULL_TOKEN that is already there', function () {
    $log = fakeGh(['GHCR_PULL_TOKEN']);
    putenv('OUTLAWZ_GHCR_PULL_TOKEN=ghp_secret');
    $directory = withRemote(radicle(), 'outlawz-team/site');

    $this->artisan('cloud:init', ['path' => $directory, '--yes' => true])
        ->doesntExpectOutputToContain('GHCR_PULL_TOKEN')
        ->assertExitCode(0);

    expect(ghCalls($log))->toBe([]);
});

it('says cloud:init needs GHCR_PULL_TOKEN when it cannot see the repository', function () {
    $this->artisan('cloud:init', ['path' => radicle(), '--yes' => true])
        ->expectsOutputToContain('gh secret set GHCR_PULL_TOKEN -R outlawz-team/<repo>')
        ->assertExitCode(0);
});
