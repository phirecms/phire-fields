<?php

return [
    APP_URI => [
        '/fields[/]' => [
            'controller' => 'Fields\Controller\IndexController',
            'action'     => 'index',
            'acl'        => [
                'resource'   => 'fields',
                'permission' => 'index'
            ]
        ],
        '/fields/add' => [
            'controller' => 'Fields\Controller\IndexController',
            'action'     => 'add',
            'acl'        => [
                'resource'   => 'fields',
                'permission' => 'add'
            ]
        ],
        '/fields/edit/:id' => [
            'controller' => 'Fields\Controller\IndexController',
            'action'     => 'edit',
            'acl'        => [
                'resource'   => 'fields',
                'permission' => 'edit'
            ]
        ],
        '/fields/remove' => [
            'controller' => 'Fields\Controller\IndexController',
            'action'     => 'remove',
            'acl'        => [
                'resource'   => 'fields',
                'permission' => 'remove'
            ]
        ],
        '/fields/groups[/]' => [
            'controller' => 'Fields\Controller\FieldGroupsController',
            'action'     => 'index',
            'acl'        => [
                'resource'   => 'field-groups',
                'permission' => 'index'
            ]
        ],
        '/fields/groups/add' => [
            'controller' => 'Fields\Controller\FieldGroupsController',
            'action'     => 'add',
            'acl'        => [
                'resource'   => 'field-groups',
                'permission' => 'add'
            ]
        ],
        '/fields/groups/edit/:id' => [
            'controller' => 'Fields\Controller\FieldGroupsController',
            'action'     => 'edit',
            'acl'        => [
                'resource'   => 'field-groups',
                'permission' => 'edit'
            ]
        ],
        '/fields/groups/remove' => [
            'controller' => 'Fields\Controller\FieldGroupsController',
            'action'     => 'remove',
            'acl'        => [
                'resource'   => 'field-groups',
                'permission' => 'remove'
            ]
        ]
    ]
];
