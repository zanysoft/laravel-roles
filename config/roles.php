<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Package Connection
    |--------------------------------------------------------------------------
    |
    | You can set a different database connection for this package. It will set
    | new connection for models Role and Permission. When this option is null,
    | it will connect to the main database, which is set up in database.php
    |
    */

    'connection' => null,

    /*
    |--------------------------------------------------------------------------
    | Slug Separator
    |--------------------------------------------------------------------------
    |
    | Here you can change the slug separator. This is very important in matter
    | of magic method __call() and also a `Slugable` trait. The default value
    | is a dot.
    |
    */

    'separator' => '.',

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | Here you can set route prefix. This is very important in matter
    | This is very important for fetching the default permissions 
    | from your controllers
    |
    */

    'route_prefix' => 'admin',

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | If you want, you can replace default models from this package by models
    | you created. Have a look at `ZanySoft\LaravelRoles\Models\Role` model and
    | `ZanySoft\LaravelRoles\Models\Permission` model.
    |
    */

    'models' => [
        'role' => ZanySoft\LaravelRoles\Models\Role::class,
        'permission' => ZanySoft\LaravelRoles\Models\Permission::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles, Permissions and Allowed "Pretend"
    |--------------------------------------------------------------------------
    |
    | You can pretend or simulate package behavior no matter what is in your
    | database. It is really useful when you are testing you application.
    | Set up what will methods hasRole(), hasPermission() and allowed() return.
    |
    */

    'pretend' => [

        'enabled' => false,

        'options' => [
            'hasRole' => true,
            'hasPermission' => true,
            'allowed' => true,
        ],

    ],

];
