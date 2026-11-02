<?php

use App\Commands\CreateRadicle;

function radicleProject(string $php, string $application): string
{
    $directory = sys_get_temp_dir() . '/radicle-' . uniqid();
    mkdir("{$directory}/.github/workflows", 0755, true);
    mkdir("{$directory}/bedrock", 0755, true);
    file_put_contents("{$directory}/composer.json", json_encode(['require' => ['php' => $php]]));
    file_put_contents("{$directory}/bedrock/application.php", $application);
    foreach (['ci.yml', 'deploy.yml', 'main.yml', 'wp-packages-changelog-action.yml', 'own.yml'] as $workflow) {
        file_put_contents("{$directory}/.github/workflows/{$workflow}", 'name: x');
    }

    return $directory;
}

function configureCloud(string $directory, array $plugins = ['wp-mail-smtp']): CreateRadicle
{
    $command = new CreateRadicle;
    $set = fn ($property, $value) => (new ReflectionProperty($command, $property))->setValue($command, $value);
    $set('folder', $directory);
    $set('directory', $directory);
    $set('data', ['plugins' => $plugins]);
    (new ReflectionMethod($command, 'configuringCloud'))->invoke($command);

    return $command;
}

$application = <<<'PHP'
<?php
/**
 * DB settings
 */
Config::define('DB_NAME', env('DB_NAME'));

/**
 * Debugging Settings
 */
Config::define('WP_DEBUG_DISPLAY', false);

Config::apply();
PHP;

it('adds the golden image workflow and removes the example workflows of Roots', function () use ($application) {
    $directory = radicleProject('>=8.4', $application);
    configureCloud($directory);

    $workflows = array_map('basename', glob("{$directory}/.github/workflows/*"));
    expect($workflows)->toEqualCanonicalizing(['image.yml', 'own.yml'])
        ->and(file_get_contents("{$directory}/.github/workflows/image.yml"))
        ->toContain('uses: outlawz-team/cloud-images/.github/workflows/wordpress.yml@wordpress/v1')
        ->toContain('secrets: inherit');
});

it('adds the DB_SSL and WP Mail SMTP blocks once, in the right place', function () use ($application) {
    $directory = radicleProject('>=8.4', $application);
    configureCloud($directory);
    $command = configureCloud($directory);

    $config = file_get_contents("{$directory}/bedrock/application.php");
    expect(substr_count($config, "if (env('DB_SSL'))"))->toBe(1)
        ->and(substr_count($config, "if (env('WPMS_ON'))"))->toBe(1)
        ->and(strpos($config, 'DB_SSL'))->toBeLessThan(strpos($config, "Config::define('DB_NAME'"))
        ->and(strpos($config, 'WPMS_ON'))->toBeLessThan(strpos($config, 'Debugging Settings'))
        ->and((new ReflectionProperty($command, 'cloudTodo'))->getValue($command))->toBe([]);
    expect(shell_exec('php -l ' . escapeshellarg("{$directory}/bedrock/application.php")))->toContain('No syntax errors');
});

it('keeps an existing DB_SSL block and says when WP Mail SMTP is missing', function () use ($application) {
    $existing = str_replace("Config::define('DB_NAME'", "if (env('DB_SSL')) {}\nConfig::define('DB_NAME'", $application);
    $directory = radicleProject('>=8.4', $existing);
    $command = configureCloud($directory, plugins: []);

    expect(substr_count(file_get_contents("{$directory}/bedrock/application.php"), 'DB_SSL'))->toBe(1)
        ->and((new ReflectionProperty($command, 'cloudTodo'))->getValue($command))
        ->toHaveCount(1)
        ->{0}->toContain('WP Mail SMTP is not installed');
});
