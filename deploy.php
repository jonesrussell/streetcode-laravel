<?php

namespace Deployer;

require 'recipe/laravel.php';

// Config

set('repository', 'git@github.com:jonesrussell/streetcode-laravel.git');
set('keep_releases', 5);

add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);

task('deploy:build_assets', function (): void {
    run('bash -lc "source ~/.nvm/nvm.sh 2>/dev/null; cd {{release_path}} && npm ci && npm run build:ssr"');
});
after('deploy:vendors', 'deploy:build_assets');

// Hosts

host('streetcode.net')
    ->set('remote_user', 'deployer')
    ->set('deploy_path', '~/streetcode-laravel');

// Hooks

after('deploy:failed', 'deploy:unlock');
before('deploy:symlink', 'artisan:migrate');

// Disable view caching — Inertia renders views client-side and artisan:view:cache
// tries to connect to the SSR server which isn't running during deploy.
task('artisan:view:cache', function (): void {});

task('deploy:install_services', function (): void {
    $serviceDir = '~/.config/systemd/user';
    run("mkdir -p $serviceDir");
    run("cp {{release_path}}/deploy/systemd-user/*.service $serviceDir/");
    run('systemctl --user daemon-reload');
    run('systemctl --user enable streetcode-horizon.service streetcode-inertia-ssr.service streetcode-schedule-work.service streetcode-articles-subscribe.service streetcode-alloy-loki.service');
});
before('deploy:symlink', 'deploy:install_services');

task('deploy:copy_caddyfile', function (): void {
    run('cp {{release_path}}/Caddyfile {{deploy_path}}/Caddyfile');
});
after('deploy:symlink', 'deploy:copy_caddyfile');

task('deploy:reload_caddy', function (): void {
    run('sudo systemctl reload caddy || true');
});
after('deploy:copy_caddyfile', 'deploy:reload_caddy');

task('deploy:restart_services', function (): void {
    run('cd {{release_path}} && {{bin/php}} artisan horizon:terminate || true');
    run('cd {{release_path}} && {{bin/php}} artisan inertia:stop-ssr || true');
    run('systemctl --user restart streetcode-horizon.service streetcode-inertia-ssr.service streetcode-schedule-work.service streetcode-articles-subscribe.service streetcode-alloy-loki.service || true');
});
after('deploy:symlink', 'deploy:restart_services');
task('deploy:reload_php_fpm', function (): void {
    run('sudo systemctl restart php8.4-fpm || true');
});
after('deploy:restart_services', 'deploy:reload_php_fpm');
