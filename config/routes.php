<?php
/**
 * phire-forms routes
 */
return [
    APP_URI => [
        '/fields[/]' => [
            'controller' => 'Phire\Fields\Controller\IndexController',
            'action'     => 'index',
            'acl'        => [
                'resource'   => 'fields',
                'permission' => 'index'
            ]
        ],
        '/fields/add' => [
            'controller' => 'Phire\Fields\Controller\IndexController',
            'action'     => 'add',
            'acl'        => [
                'resource'   => 'fields',
                'permission' => 'add'
            ]
        ],
        '/fields/edit/:id' => [
            'controller' => 'Phire\Fields\Controller\IndexController',
            'action'     => 'edit',
            'acl'        => [
                'resource'   => 'fields',
                'permission' => 'edit'
            ]
        ],
        '/fields/remove' => [
            'controller' => 'Phire\Fields\Controller\IndexController',
            'action'     => 'remove',
            'acl'        => [
                'resource'   => 'fields',
                'permission' => 'remove'
            ]
        ],
        '/fields/json/:model[/:fid][/:marked]' => [
            'controller' => 'Phire\Fields\Controller\IndexController',
            'action'     => 'json'
        ],
        '/fields/browser' => [
            'controller' => 'Phire\Fields\Controller\IndexController',
            'action'     => 'browser',
            'acl'        => [
                'resource'   => 'fields',
                'permission' => 'browser'
            ]
        ],
        '/fields/groups[/]' => [
            'controller' => 'Phire\Fields\Controller\GroupsController',
            'action'     => 'index',
            'acl'        => [
                'resource'   => 'field-groups',
                'permission' => 'index'
            ]
        ],
        '/fields/groups/add' => [
            'controller' => 'Phire\Fields\Controller\GroupsController',
            'action'     => 'add',
            'acl'        => [
                'resource'   => 'field-groups',
                'permission' => 'add'
            ]
        ],
        '/fields/groups/edit/:id' => [
            'controller' => 'Phire\Fields\Controller\GroupsController',
            'action'     => 'edit',
            'acl'        => [
                'resource'   => 'field-groups',
                'permission' => 'edit'
            ]
        ],
        '/fields/groups/remove' => [
            'controller' => 'Phire\Fields\Controller\GroupsController',
            'action'     => 'remove',
            'acl'        => [
                'resource'   => 'field-groups',
                'permission' => 'remove'
            ]
        ]
    ]
];
