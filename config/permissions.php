<?php

return [
    'table_names' => [
        /**
         * Contains roles and permissions
         */
        'auth_items' => 'auth_items',

        /**
         * Inheritances of roles and permissions are specified here
         */
        'auth_item_childs' => 'auth_item_childs',

        /**
         * Assignations of users or any other classes with roles and permissions
         */
        'auth_assignments' => 'auth_assignments',
    ],
    'cache' => [
        /**
         * Is the cache on?
         */
        'enable' => false,
        /**
         * Name of cache key for auth items
         */
        'key_name' => 'centeron.permissions',
        /**
         * By default all auth items (roles ans permisssions) cached for 24 hours unless
         * these items will updated by supplied functions for their manipulating
         */
        'cache_lifetime' => 60 * 24
    ]
];