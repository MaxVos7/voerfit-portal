<?php

beforeEach(function () {
    createFarmSchema();
});

function configurePortalPassword(): void
{
    config([
        'portal.basic_auth.username' => 'nijhof',
        'portal.basic_auth.password' => 'correct horse',
    ]);
}

it('returns 401 with a login prompt when no credentials are sent', function () {
    configurePortalPassword();

    $this->get(route('day-overview'))
        ->assertUnauthorized()
        ->assertHeader('WWW-Authenticate', 'Basic realm="Voerfit", charset="UTF-8"');
});

it('returns 401 for wrong credentials', function (string $username, string $password) {
    configurePortalPassword();

    $this->withBasicAuth($username, $password)
        ->get(route('day-overview'))
        ->assertUnauthorized();
})->with([
    'wrong password' => ['nijhof', 'wrong'],
    'wrong username' => ['someone', 'correct horse'],
    'empty password' => ['nijhof', ''],
]);

it('shows the page for the correct credentials', function () {
    configurePortalPassword();

    $this->withBasicAuth('nijhof', 'correct horse')
        ->get(route('day-overview'))
        ->assertOk();
});

it('leaves the health check open for the load balancer', function () {
    configurePortalPassword();

    $this->get('/up')->assertOk();
});

it('lets requests through when no password is configured locally', function () {
    config(['portal.basic_auth.username' => null, 'portal.basic_auth.password' => null]);

    $this->get(route('day-overview'))->assertOk();
});

it('returns 401 in production when no password is configured', function () {
    config(['portal.basic_auth.username' => null, 'portal.basic_auth.password' => null]);
    app()->detectEnvironment(fn () => 'production');

    $this->withBasicAuth('', '')
        ->get(route('day-overview'))
        ->assertUnauthorized();
});
