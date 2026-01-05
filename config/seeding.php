<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default User Passwords
    |--------------------------------------------------------------------------
    |
    | These passwords are used when seeding the database with default users.
    | Change these in production or set them in your .env file.
    |
    */

    'passwords' => [
        'admin' => env('ADMIN_PASSWORD', 'admin'),
        'dev' => env('DEV_PASSWORD', 'dev'),
        'user' => env('USER_PASSWORD', 'user'),
    ],

];

