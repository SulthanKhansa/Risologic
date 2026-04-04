<?php

return [

    'models' => [

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * Eloquent model should be used to retrieve your permissions. Of course, it
         * is often just the "Permission" model but you may use whatever you like.
         *
         * The model you want to use as a Permission model needs to implement the
         * `Spatie\Permission\Contracts\Permission` contract.
         */

        'permission' => Spatie\Permission\Models\Permission::class,

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * Eloquent model should be used to retrieve your roles. Of course, it
         * is often just the "Role" model but you may use whatever you like.
         *
         * The model you want to use as a Role model needs to implement the
         * `Spatie\Permission\Contracts\Role` contract.
         */

        'role' => Spatie\Permission\Models\Role::class,

    ],

    'table_names' => [

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * table should be used to retrieve your roles. We have chosen a basic
         * default value but you may easily change it to any table you like.
         */

        'roles' => 'roles',

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * table should be used to retrieve your permissions. We have chosen a basic
         * default value but you may easily change it to any table you like.
         */

        'permissions' => 'permissions',

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * table should be used to retrieve your models permissions. We have chosen a
         * basic default value but you may easily change it to any table you like.
         */

        'model_has_permissions' => 'model_has_permissions',

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * table should be used to retrieve your models roles. We have chosen a
         * basic default value but you may easily change it to any table you like.
         */

        'model_has_roles' => 'model_has_roles',

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * table should be used to retrieve your roles permissions. We have chosen a
         * basic default value but you may easily change it to any table you like.
         */

        'role_has_permissions' => 'role_has_permissions',
    ],

    'column_names' => [
        /*
         * Change this if you want to name the related model primary key other than
         * `model_id`.
         *
         * For users of Laravel < 5.3, the default should be 'chart_id'.
         */
        'model_morph_key' => 'model_id',

        /*
         * Change this if you want to name the related team foreign key other than
         * `team_id`.
         */
        'team_foreign_key' => 'team_id',
    ],

    /*
     * When set to true, the "StatusHasPermissions" check will be registered on the IDP.
     */

    'register_permission_check' => true,

    /*
     * When set to true, the team feature will be enabled.
     * See the documentation for more information about the team feature.
     */

    'teams' => false,

    /*
     * When set to true, the required permission names are case sensitive.
     */

    'display_permission_in_exception' => false,

    /*
     * When set to true, the required role names are case sensitive.
     */

    'display_role_in_exception' => false,

    /*
     * By default, Laravel-permission responses follow the Laravel naming convention.
     * Set this to true to use the naming convention of the package.
     */

    'enable_wildcard_permission' => false,

    'cache' => [

        /*
         * By default all permissions are cached for 24 hours to speed up performance.
         * When permissions or roles are updated the cache is automatically flushed.
         */

        'expiration_time' => \DateInterval::createFromDateString('24 hours'),

        /*
         * The key to use for storing all permissions in the cache.
         */

        'key' => 'spatie.permission.cache',

        /*
         * You may optionally specify a specific cache store to use for caching
         * permissions. This must be the name of a store from your config/cache.php
         * file. Use 'default' to use the application's default cache store.
         */

        'store' => 'default',
    ],
];
