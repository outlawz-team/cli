<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Golden image
    |--------------------------------------------------------------------------
    |
    | The ref of outlawz-team/cloud-images whose WordPress workflow builds the
    | image of a new Radicle project. The major version moves along with the
    | releases of the golden image; a new major version is a change here.
    |
    */

    'cloud_images_ref' => 'wordpress/v1',

    /*
    |--------------------------------------------------------------------------
    | Example workflows
    |--------------------------------------------------------------------------
    |
    | The workflows Roots ships with Radicle. Outlawz Cloud only needs
    | image.yml: these run on a branch main the projects do not have, or
    | test Radicle's own examples.
    |
    */

    'example_workflows' => ['ci.yml', 'deploy.yml', 'main.yml', 'wp-packages-changelog-action.yml'],

];
