<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bus processes tables
    |--------------------------------------------------------------------------
    |
    | These options configure tables name for Processes. You are free to set
    | them as you want. Remember that these are most important tables is your
    | future system. You can use different database to store them.
    |
    */

    'database' => env('BUS_CONNECTION', env('DB_CONNECTION')),
    'table'    => 'bus_processes',

    /*
    |--------------------------------------------------------------------------
    | User table configuration
    |--------------------------------------------------------------------------
    |
    | Process bus uses Auth user id to log who is the owner of process.
    | In large systems it's very helpful to log this kind of information.
    |
    | Supported primary: Methods used by Schema to create columns.
    |
    */

    'users' => [
        'table'   => env('BUS_USER_TABLE', 'users'),
        'primary' => [
            'type' => env('BUS_USER_PRIMARY_TYPE', 'unsignedBigInteger'),
            'name' => env('BUS_USER_PRIMARY_NAME', 'id')
        ]
    ]
];
