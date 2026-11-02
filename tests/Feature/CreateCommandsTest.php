<?php

use App\Commands\CreatePlatform;
use App\Commands\CreateRadicle;

it('generates salts that fit in .env', function () {
    $salts = call(new CreateRadicle, 'salts');

    expect($salts)->toHaveCount(8)->toHaveKeys(['AUTH_KEY', 'NONCE_SALT'])
        ->and(count(array_unique($salts)))->toBe(8);
    foreach ($salts as $salt) {
        expect(strlen($salt))->toBe(64)
            ->and($salt)->not->toMatch('/[\'"\\\\$`\s]/');
    }
});

it('only takes database names that are safe between backticks', function (string $name, bool $valid) {
    expect(call(new CreateRadicle, 'validDatabaseName', $name))->toBe($valid);
})->with([
    ['my_site', true],
    ['my-site', true],
    ['site`; DROP DATABASE x; --', false],
    ["site'", false],
    ['', false],
]);

it('refuses a folder with characters a shell or database could trip over', function (string $command) {
    $this->artisan($command, ['folder' => 'my site; rm -rf ~'])
        ->expectsOutputToContain('may only contain')
        ->assertExitCode(1);
})->with(['create:radicle', 'create:platform']);

it('makes a new Radicle ready for Outlawz Cloud', function () {
    $directory = radicle();
    $command = set(new CreateRadicle, ['folder' => $directory, 'directory' => $directory, 'data' => ['plugins' => ['wp-mail-smtp']]]);

    call($command, 'configuringCloud');

    expect(array_map('basename', glob("{$directory}/.github/workflows/*")))->toBe(['image.yml'])
        ->and(file_get_contents("{$directory}/bedrock/application.php"))->toContain("if (env('WPMS_ON'))")
        ->and(get($command, 'cloudTodo'))->toBe([]);
});

it('says when WP Mail SMTP is not chosen', function () {
    $directory = radicle();
    $command = set(new CreateRadicle, ['folder' => $directory, 'directory' => $directory, 'data' => ['plugins' => []]]);

    call($command, 'configuringCloud');

    expect(get($command, 'cloudTodo'))->toHaveCount(1)->{0}->toContain('WP Mail SMTP is not installed');
});

it('names a new platform after the project', function () {
    $directory = project([
        'apps/api/.env.example' => "APP_NAME=Laravel\nAPP_ENV=local\n",
        'apps/api/composer.json' => json_encode(['name' => 'laravel/laravel', 'require' => ['php' => '^8.3']], JSON_PRETTY_PRINT),
        'apps/dashboard/package.json' => json_encode(['name' => 'dashboard'], JSON_PRETTY_PRINT),
        'bruno/bruno.json' => "{\n  \"version\": \"1\",\n  \"name\": \"API\",\n  \"type\": \"collection\"\n}\n",
    ]);
    $command = set(new CreatePlatform, ['folder' => 'mijn-platform', 'directory' => $directory, 'data' => ['name' => 'Mijn "Platform"']]);

    call($command, 'naming');

    expect(file_get_contents("{$directory}/apps/api/.env.example"))->toStartWith("APP_NAME=\"Mijn Platform\"\n")
        ->and(json_decode(file_get_contents("{$directory}/apps/api/composer.json"), true))->toMatchArray(['name' => 'outlawz-team/mijn-platform-api', 'description' => 'The API of Mijn Platform'])
        ->and(json_decode(file_get_contents("{$directory}/apps/dashboard/package.json"), true)['name'])->toBe('mijn-platform-dashboard')
        ->and(json_decode(file_get_contents("{$directory}/bruno/bruno.json"), true))->toBe(['version' => '1', 'name' => 'Mijn Platform', 'type' => 'collection']);
});
