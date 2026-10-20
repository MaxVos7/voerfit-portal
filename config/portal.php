<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Shared portal password
    |--------------------------------------------------------------------------
    |
    | Until customers have their own login, the whole portal sits behind one
    | shared HTTP Basic Auth username and password. Outside local and testing
    | environments the portal refuses every request when these are not set.
    |
    */

    'basic_auth' => [
        'username' => env('PORTAL_USERNAME'),
        'password' => env('PORTAL_PASSWORD'),
    ],

];
