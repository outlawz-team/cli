<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * An empty folder in the temp directory, with $files (path => contents) in it.
 */
function project(array $files = []): string
{
    $directory = sys_get_temp_dir() . '/outlawz-cli-' . uniqid();
    mkdir($directory);

    foreach ($files as $path => $contents) {
        is_dir(dirname("{$directory}/{$path}")) || mkdir(dirname("{$directory}/{$path}"), 0755, true);
        file_put_contents("{$directory}/{$path}", $contents);
    }

    return $directory;
}

/**
 * A Radicle as Roots ships it, as far as Outlawz Cloud goes.
 */
function radicle(array $files = []): string
{
    $workflows = [];
    foreach (glob(__DIR__ . '/Fixtures/workflows/*.yml') as $workflow) {
        $workflows['.github/workflows/' . basename($workflow)] = file_get_contents($workflow);
    }

    return project([
        'composer.json' => json_encode(['require' => ['php' => '>=8.4']]),
        'bedrock/application.php' => file_get_contents(__DIR__ . '/Fixtures/radicle-application.php'),
        'public/content/plugins/.gitkeep' => '',
        ...$workflows,
        ...$files,
    ]);
}

/**
 * Call a protected method of $object.
 */
function call(object $object, string $method, ...$arguments)
{
    return (new ReflectionMethod($object, $method))->invoke($object, ...$arguments);
}

/**
 * Set protected properties of $object.
 */
function set(object $object, array $properties): object
{
    foreach ($properties as $property => $value) {
        (new ReflectionProperty($object, $property))->setValue($object, $value);
    }

    return $object;
}

/**
 * A protected property of $object.
 */
function get(object $object, string $property)
{
    return (new ReflectionProperty($object, $property))->getValue($object);
}
