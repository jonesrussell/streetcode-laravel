<?php
namespace Deployer;

require 'recipe/laravel.php';

// Config

set('repository', 'git@github.com:jonesrussell/streetcode-laravel.git');

add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);

// Hosts

host('streetcode.net')
    ->set('remote_user', 'deployer')
    ->set('deploy_path', '~/streetcode-laravel');

// Hooks

after('deploy:failed', 'deploy:unlock');
