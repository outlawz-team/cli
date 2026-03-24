<?php

echo 'What domain name should Trellis be configured for? (e.g. example.com): ';
$domain = trim(fgets(STDIN));

// Create a new Trellis project using the CLI
// The "." specifies the current directory.
// The "--force" flag is used because the directory is not empty.
// The "--name" flag sets the domain, bypassing the interactive prompt from the CLI.
// The "--skip-bedrock" flag is used because we already have a Bedrock setup.
passthru('trellis new --force --skip-bedrock --name ' . escapeshellarg($domain) . ' .');

// In trellis/group_vars/all/main.yml:
// - Update php_version from default 8.3 to 8.4
// - Add wp_cli_packages after `max_journal_size: 512M`
$groupVarsAllMain = file_get_contents('trellis/group_vars/all/main.yml');
$groupVarsAllMain = str_replace('php_version: "8.3"', 'php_version: "8.4"', $groupVarsAllMain);
$groupVarsAllMain = str_replace(
    "max_journal_size: 512M",
    "max_journal_size: 512M \n\nwp_cli_packages:\n  - aaemnnosttv/wp-cli-login-command",
    $groupVarsAllMain
);
file_put_contents('trellis/group_vars/all/main.yml', $groupVarsAllMain);

// Overwrite `trellis/deploy-hooks/build-after.yml` with `.radicle-setup/trellis/build-after.yml`
// Overwrite `trellis/deploy-hooks/build-before.yml` with `.radicle-setup/trellis/build-before.yml`
copy('.radicle-setup/trellis/build-after.yml', 'trellis/deploy-hooks/build-after.yml');
copy('.radicle-setup/trellis/build-before.yml', 'trellis/deploy-hooks/build-before.yml');

// In the following files:
// - group_vars/development/wordpress_sites.yml
// - group_vars/staging/wordpress_sites.yml
// - group_vars/production/wordpress_sites.yml
//
// Replace: `local_path: ../site` with `local_path: ..`
// After `local_path`, add two new lines with four spaces in front of them:
// `public_path: public`
// `upload_path: content/uploads`
//
// Get rid of the entire line: `repo_subtree_path: site # relative path to your Bedrock/WP directory in your repo`
$wordpressSites = [
    'group_vars/development/wordpress_sites.yml',
    'group_vars/staging/wordpress_sites.yml',
    'group_vars/production/wordpress_sites.yml',
];
foreach ($wordpressSites as $file) {
    if (file_exists('trellis/' . $file)) {
        $content = file_get_contents('trellis/' . $file);
        // Replace the local_path line with the new structure
        $content = preg_replace(
            "/^(\s*)local_path: .*$/m",
            "$1local_path: ..\n$1public_path: public\n$1upload_path: content/uploads",
            $content
        );
        // Remove the repo_subtree_path line if it exists
        $content = preg_replace(
            "/^\s*repo_subtree_path: .*$/m",
            '',
            $content
        );
        // Remove empty lines that might be left after removing repo_subtree_path
        $content = preg_replace(
            "/^\s*\n/m",
            '',
            $content
        );

        file_put_contents('trellis/' . $file, $content);
    }
}

// Replace `192.168.56.5` with `192.168.56.8` in trellis/hosts/development
file_put_contents('trellis/hosts/development', str_replace('192.168.56.5', '192.168.56.8', file_get_contents('trellis/hosts/development')));
