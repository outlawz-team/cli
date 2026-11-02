<?php

it('moves a Radicle from its own Dockerfile to the golden image', function () {
    $directory = radicle([
        'Dockerfile' => 'FROM php',
        'docker/nginx.conf' => 'server {}',
        '.github/workflows/image.yml' => 'name: Image (own Dockerfile)',
        'package.json' => json_encode(['devDependencies' => ['vite' => '*']]),
    ]);

    $this->artisan('cloud:init', ['path' => $directory, '--yes' => true])->assertExitCode(0);

    expect(file_exists("{$directory}/Dockerfile"))->toBeFalse()
        ->and(is_dir("{$directory}/docker"))->toBeFalse()
        ->and(array_map('basename', glob("{$directory}/.github/workflows/*")))->toBe(['image.yml'])
        ->and(file_get_contents("{$directory}/.github/workflows/image.yml"))->toContain('workflows/wordpress.yml@wordpress/v1')
        ->and(file_get_contents("{$directory}/bedrock/application.php"))->toContain("if (env('WPMS_ON'))")
        ->and(file_exists("{$directory}/cloud/config.yml"))->toBeFalse();
});

it('gives a Radicle that builds with bud the assets command', function () {
    $directory = radicle(['package.json' => json_encode(['devDependencies' => ['@roots/bud' => '*']], JSON_UNESCAPED_SLASHES)]);

    $this->artisan('cloud:init', ['path' => $directory, '--yes' => true])->assertExitCode(0);

    expect(file_get_contents("{$directory}/cloud/config.yml"))->toContain('command: yarn bud build production');
});

it('changes nothing without asking', function () {
    $directory = radicle(['Dockerfile' => 'FROM php', 'composer.json' => json_encode(['require' => ['php' => '>=8.2']])]);
    $config = file_get_contents("{$directory}/bedrock/application.php");

    $this->artisan('cloud:init', ['path' => $directory, '--no-interaction' => true])
        ->expectsOutputToContain('PHP requirement >=8.2')
        ->assertExitCode(0);

    expect(file_exists("{$directory}/Dockerfile"))->toBeTrue()
        ->and(file_exists("{$directory}/.github/workflows/ci.yml"))->toBeTrue()
        ->and(file_get_contents("{$directory}/bedrock/application.php"))->toBe($config)
        ->and(file_get_contents("{$directory}/composer.json"))->toContain('>=8.2');
});

it('keeps an image.yml that already builds on the golden image', function () {
    $own = "jobs:\n  image:\n    uses: outlawz-team/cloud-images/.github/workflows/wordpress.yml@wordpress/v1\n    with: { extra: true }\n";
    $directory = radicle(['.github/workflows/image.yml' => $own]);

    $this->artisan('cloud:init', ['path' => $directory, '--yes' => true])->assertExitCode(0);

    expect(file_get_contents("{$directory}/.github/workflows/image.yml"))->toBe($own);
});

it('makes a platform ready for the golden image of the platform', function () {
    $directory = project([
        'apps/api/artisan' => '',
        'apps/api/composer.json' => json_encode(['require' => ['php' => '^8.4']]),
        'apps/dashboard/next.config.ts' => "const nextConfig: NextConfig = {}\n",
    ]);

    $this->artisan('cloud:init', ['path' => $directory, '--yes' => true])->assertExitCode(0);

    expect(file_get_contents("{$directory}/.github/workflows/image.yml"))->toContain('workflows/platform.yml@platform/v1')
        ->and(file_get_contents("{$directory}/apps/dashboard/next.config.ts"))->toContain('output: "standalone"');
});

it('says what the build needs for private packages', function () {
    $directory = radicle(['composer.json' => json_encode([
        'require' => ['php' => '>=8.4', 'wpackagist-plugin/wp-mail-smtp' => '*'],
        'repositories' => [['type' => 'composer', 'url' => 'https://connect.advancedcustomfields.com']],
    ])]);

    $this->artisan('cloud:init', ['path' => $directory, '--yes' => true])
        ->expectsOutputToContain('COMPOSER_AUTH')
        ->assertExitCode(0);
});

it('refuses what the golden image does not fit', function (array $files, string $message) {
    $this->artisan('cloud:init', ['path' => project($files)])
        ->expectsOutputToContain($message)
        ->assertExitCode(1);
})->with([
    'bedrock in web/app' => [['config/application.php' => '', 'web/app/.gitkeep' => ''], 'Bedrock with web/app'],
    'winter' => [['artisan' => '', 'modules/system/.gitkeep' => ''], 'Winter CMS'],
    'something else' => [['index.php' => ''], 'No Radicle, Bedrock or platform'],
]);
