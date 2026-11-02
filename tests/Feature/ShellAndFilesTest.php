<?php

use App\Commands\Traits\Files;
use App\Commands\Traits\Shell;

function tools(): object
{
    return new class {
        use Files, Shell;
    };
}

it('passes arguments without a shell', function () {
    $argument = "it's \"quoted\" \$HOME; rm -rf /";

    expect(tools()->shell(['php', '-r', 'echo $argv[1];', $argument], quiet: true))->toBe($argument);
});

it('stops when a command fails', function () {
    tools()->shell(['php', '-r', 'fwrite(STDERR, "broken"); exit(3);'], quiet: true);
})->throws(RuntimeException::class, 'broken');

it('finds the tools that are missing', function () {
    expect(tools()->missingTools(['git', 'php']))->toBe([]);
});

it('empties a directory with hidden files in it', function () {
    $directory = project(['folder/.git/HEAD' => 'ref', 'folder/.gitkeep' => '', 'folder/file' => 'x']);

    tools()->emptyDirectory("{$directory}/folder");
    expect(scandir("{$directory}/folder"))->toBe(['.', '..']);

    tools()->deleteDirectory("{$directory}/folder");
    expect(is_dir("{$directory}/folder"))->toBeFalse();
});
