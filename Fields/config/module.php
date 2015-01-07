<?php
/**
 * Module Name: Fields
 * Author: Nick Sagona
 * Description: This is the fields module for Phire CMS 2
 * Version: 1.0
 */
return [
    'Fields' => [
        'prefix'     => 'Fields\\',
        'src'        => __DIR__ . '/../src',
        'routes'     => include 'routes.php',
        'resources'  => include 'resources.php',
        'forms'      => include 'forms.php',
        'nav.phire'  => [
            'fields' => [
                'name' => 'Fields',
                'href' => '/fields',
                'acl' => [
                    'resource'   => 'fields',
                    'permission' => 'index'
                ],
                'children' => [
                    'field-groups' => [
                        'name' => 'Field Groups',
                        'href' => '/fields/groups',
                        'acl'  => [
                            'resource'   => 'field-groups',
                            'permission' => 'index'
                        ]
                    ]
                ]
            ]
        ],
        'events' => [
            [
                'name'   => 'app.route.pre',
                'action' => 'Fields\Model\Field::addModels'
            ],
            [
                'name'   => 'app.dispatch.pre',
                'action' => 'Fields\Model\Field::addFields'
            ],
            [
                'name'   => 'app.send',
                'action' => 'Fields\Model\Field::getFieldValues'
            ],
            [
                'name'   => 'app.send',
                'action' => 'Fields\Model\Field::saveFieldValues'
            ],
            [
                'name'   => 'app.send',
                'action' => 'Fields\Model\Field::deleteFieldValues'
            ]
        ],
        'models' => [
            'Phire\Model\User'     => [],
            'Phire\Model\UserRole' => []
        ],
        'history' => 10
    ]
];
