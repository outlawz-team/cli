<?php

use App\Commands\Traits\Cloud;
use App\Commands\Traits\Files;
use App\Commands\Traits\Shell;

function cloud(): object
{
    return new class {
        use Cloud, Files, Shell;
    };
}

it('writes image.yml on the golden image and removes only the example workflows of Roots', function () {
    $directory = radicle(['.github/workflows/own.yml' => 'name: Own', '.github/workflows/main.yml' => 'name: changed by us']);

    $removed = cloud()->cloudWorkflow($directory, 'wordpress');

    expect($removed)->toEqualCanonicalizing(['.github/workflows/ci.yml', '.github/workflows/deploy.yml', '.github/workflows/wp-packages-changelog-action.yml'])
        ->and(array_map('basename', glob("{$directory}/.github/workflows/*")))->toEqualCanonicalizing(['image.yml', 'main.yml', 'own.yml'])
        ->and(file_get_contents("{$directory}/.github/workflows/image.yml"))
        ->toContain('uses: outlawz-team/cloud-images/.github/workflows/wordpress.yml@wordpress/v1')
        ->toContain('secrets: inherit');
});

it('writes image.yml for the platform', function () {
    $directory = project();
    cloud()->writeCloudWorkflow($directory, 'platform');

    expect(file_get_contents("{$directory}/.github/workflows/image.yml"))->toContain('workflows/platform.yml@platform/v1')
        ->and(cloud()->hasCloudWorkflow($directory))->toBeTrue();
});

it('knows which PHP requirement the golden image builds on', function (string $requirement, string $stack, ?string $version) {
    $directory = project(['composer.json' => json_encode(['require' => $requirement === '' ? [] : ['php' => $requirement]])]);

    expect(cloud()->cloudPhpVersion("{$directory}/composer.json", $stack))->toBe($version);
})->with([
    ['>=8.4', 'wordpress', '8.4'],
    ['^8.3', 'wordpress', '8.3'],
    ['~8.4.0', 'wordpress', '8.4'],
    ['8.4.*', 'wordpress', '8.4'],
    ['>= 8.4', 'wordpress', '8.4'],
    ['^8.3', 'platform', null],
    ['^8.4', 'platform', '8.4'],
    ['>=8.2', 'wordpress', null],
    ['8.4', 'wordpress', null],
    ['^8.3 || ^8.4', 'wordpress', null],
    ['', 'wordpress', null],
]);

it('adds the WP Mail SMTP block to the configuration of Radicle once, and keeps its DB_SSL block', function () {
    $directory = radicle();
    $config = "{$directory}/bedrock/application.php";

    expect(cloud()->wordpressCloudConfig($config))->toBe([])
        ->and(cloud()->wordpressCloudConfig($config))->toBe([]);

    $contents = file_get_contents($config);
    expect(substr_count($contents, "if (env('DB_SSL'))"))->toBe(1)
        ->and(substr_count($contents, "if (env('WPMS_ON'))"))->toBe(1)
        ->and(strpos($contents, 'WPMS_ON'))->toBeLessThan(strpos($contents, 'Debugging Settings'))
        ->and(shell_exec('php -l ' . escapeshellarg($config)))->toContain('No syntax errors');
});

it('adds the DB_SSL block before the database settings', function () {
    $directory = project(['application.php' => "<?php\nConfig::define('DB_NAME', env('DB_NAME'));\nConfig::apply();\n"]);

    expect(cloud()->wordpressCloudConfig("{$directory}/application.php"))->toBe([]);
    $contents = file_get_contents("{$directory}/application.php");
    expect(strpos($contents, 'DB_SSL'))->toBeLessThan(strpos($contents, "Config::define('DB_NAME'"))
        ->and(strpos($contents, 'WPMS_ON'))->toBeLessThan(strpos($contents, 'Config::apply();'));
});

it('says what it cannot add to a configuration it does not know', function () {
    $directory = project(['application.php' => "<?php\n"]);

    expect(cloud()->wordpressCloudConfig("{$directory}/application.php"))->toHaveCount(2);
});

it('sets output standalone in next.config', function (string $before, string $after) {
    $directory = project(['next.config.ts' => $before]);

    expect(cloud()->nextStandalone($directory))->toBeTrue()
        ->and(file_get_contents("{$directory}/next.config.ts"))->toBe($after);
})->with([
    'empty' => ["const nextConfig: NextConfig = {}\n", "const nextConfig: NextConfig = {\n  output: \"standalone\",\n}\n"],
    'with settings' => ["const nextConfig = {\n  reactStrictMode: true,\n}\n", "const nextConfig = {\n  output: \"standalone\",\n  reactStrictMode: true,\n}\n"],
    'already' => ["const nextConfig = { output: 'standalone' }\n", "const nextConfig = { output: 'standalone' }\n"],
]);

it('says when it cannot set output standalone', function () {
    expect(cloud()->nextStandalone(project(['next.config.js' => 'module.exports = withPlugins()'])))->toBeFalse()
        ->and(cloud()->nextStandalone(project()))->toBeFalse();
});

it('finds the private packages the build needs COMPOSER_AUTH for', function () {
    $directory = project(['composer.json' => json_encode(['repositories' => [
        ['type' => 'composer', 'url' => 'https://wpackagist.org'],
        ['type' => 'composer', 'url' => 'https://connect.advancedcustomfields.com'],
        ['type' => 'vcs', 'url' => 'git@github.com:outlawz-team/plugin.git'],
    ]])]);

    expect(cloud()->privatePackages("{$directory}/composer.json"))->toBe(['https://connect.advancedcustomfields.com', 'git@github.com:outlawz-team/plugin.git']);
});

it('reads the GitHub repository of origin', function (string $url, ?string $repository) {
    $directory = project();
    exec('git -C ' . escapeshellarg($directory) . ' init -q && git -C ' . escapeshellarg($directory) . ' remote add origin ' . escapeshellarg($url));

    expect(cloud()->githubRepositoryOf($directory))->toBe($repository);
})->with([
    ['git@github.com:outlawz-team/site.git', 'outlawz-team/site'],
    ['https://github.com/outlawz-team/site', 'outlawz-team/site'],
    ['git@gitlab.com:outlawz/site.git', null],
]);
