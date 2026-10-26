<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates a verified user who can log in', function () {
    $this->artisan('portal:create-user', ['name' => 'Van Velde', 'email' => 'boer@example.com'])
        ->expectsQuestion('Password', 'secret-password-1')
        ->assertSuccessful();

    $user = User::where('email', 'boer@example.com')->sole();

    expect($user->name)->toBe('Van Velde')
        ->and($user->herd_id)->toBeNull()
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and(Hash::check('secret-password-1', $user->password))->toBeTrue();
});

it('limits the user to a herd when one is given', function () {
    $this->artisan('portal:create-user', ['name' => 'Van Velde', 'email' => 'boer@example.com', '--herd' => 1])
        ->expectsQuestion('Password', 'secret-password-1')
        ->assertSuccessful();

    expect(User::where('email', 'boer@example.com')->sole()->herd_id)->toBe(1);
});

it('refuses an email address that is already in use', function () {
    User::factory()->create(['email' => 'boer@example.com']);

    $this->artisan('portal:create-user', ['name' => 'Van Velde', 'email' => 'boer@example.com'])
        ->expectsQuestion('Password', 'secret-password-1')
        ->assertFailed();

    expect(User::count())->toBe(1);
});

it('refuses a password that is too short', function () {
    $this->artisan('portal:create-user', ['name' => 'Van Velde', 'email' => 'boer@example.com'])
        ->expectsQuestion('Password', 'short')
        ->assertFailed();

    expect(User::count())->toBe(0);
});
