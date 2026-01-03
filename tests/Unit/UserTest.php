<?php

use App\Models\User;

it('has is_admin field defaulting to false', function () {
    $user = User::factory()->create();

    expect($user->is_admin)->toBeFalse();
});

it('can be created as admin', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->is_admin)->toBeTrue();
});

it('can check if user is admin', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false]);

    expect($admin->is_admin)->toBeTrue();
    expect($user->is_admin)->toBeFalse();
});
