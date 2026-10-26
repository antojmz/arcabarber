<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Initial administrator
    |--------------------------------------------------------------------------
    |
    | Account created by the database seeder when it does not exist yet. Set
    | these values in the .env file before seeding a production database.
    |
    */

    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrador'),
        'email' => env('ADMIN_EMAIL', 'admin@arcabarber.test'),
        'password' => env('ADMIN_PASSWORD', 'password'),
    ],

];
