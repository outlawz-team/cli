<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Golden images
    |--------------------------------------------------------------------------
    |
    | Per stack of outlawz-team/cloud-images: the ref of its workflow that
    | builds the image of a project, the PHP versions it has a runtime image
    | for, and the requirement a new project gets. The major version moves
    | along with the releases of the golden image; a new major version is a
    | change here.
    |
    */

    'stacks' => [
        'wordpress' => [
            'ref' => 'wordpress/v1',
            'php' => ['8.3', '8.4'],
            'requirement' => '>=8.4',
        ],
        'platform' => [
            'ref' => 'platform/v1',
            'php' => ['8.4'],
            'requirement' => '^8.4',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Example workflows
    |--------------------------------------------------------------------------
    |
    | The workflows Roots ships with Radicle and Bedrock, with a line only
    | that example has. Outlawz Cloud only needs image.yml: these run on a
    | branch main the projects do not have, or test Radicle's own examples.
    | A workflow the team changed itself has other lines and stays.
    |
    */

    'example_workflows' => [
        'ci.yml' => 'devcontainers/ci@',
        'deploy.yml' => 'This is an example deploy job',
        'main.yml' => "contains(github.event.head_commit.message, '[ci skip]')",
        'wp-packages-changelog-action.yml' => 'roots/wp-packages-changelog-action@',
    ],

    /*
    |--------------------------------------------------------------------------
    | GitHub
    |--------------------------------------------------------------------------
    |
    | The organization new repositories are created in.
    |
    */

    'organization' => 'outlawz-team',

];
