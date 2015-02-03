<?php

return [
    'Fields\Form\Field' => [
        [
            'submit' => [
                'type'       => 'submit',
                'value'      => 'Save',
                'attributes' => [
                    'class'  => 'save-btn wide'
                ]
            ],
            'placement' => [
                'type'  => 'select',
                'label' => 'Field Placement',
                'value' => [
                    'append'  => 'Append',
                    'prepend' => 'Prepend'
                ]
            ],
            'group_id' => [
                'type'  => 'select',
                'label' => 'Field Group',
                'value' => null
            ],
            'order' => [
                'type'       => 'text',
                'label'      => 'Order',
                'attributes' => ['size' => 3],
                'value'      => 0
            ],
            'required' => [
                'type'  => 'radio',
                'label' => 'Required',
                'value' => [
                    '1' => 'Yes',
                    '0' => 'No'
                ],
                'marked' => 0
            ],
            'encrypt' => [
                'type'  => 'radio',
                'label' => 'Encrypt',
                'value' => [
                    '1' => 'Yes',
                    '0' => 'No'
                ],
                'marked' => 0
            ],
            'id' => [
                'type'  => 'hidden',
                'value' => 0
            ]
        ],
        [
            'type' => [
                'type'     => 'select',
                'label'    => 'Field Type',
                'required' => true,
                'value'    => [
                    'text'             => 'text',
                    'textarea'         => 'textarea',
                    'textarea-history' => 'textarea-history',
                    'email'            => 'email',
                    'select'           => 'select',
                    'checkbox'         => 'checkbox',
                    'radio'            => 'radio',
                    'file'             => 'file',
                    'hidden'           => 'hidden'
                ],
                'attributes' => [
                    'onchange' => 'phire.toggleEditor(this);'
                ]
            ],
            'editor' => [
                'type'       => 'select',
                'value'      => ['source' => 'Source'],
                'marked'     => 'source',
                'attributes' => [
                    'style' => 'display: none;'
                ]
            ],
            'name' => [
                'type'       => 'text',
                'label'      => 'Field Name',
                'required'   => true,
                'attributes' => ['size' => 60]
            ],
            'label' => [
                'type'       => 'text',
                'label'      => 'Field Label',
                'attributes' => ['size' => 60]
            ],
            'values' => [
                'type'       => 'text',
                'label'      => 'Field Values (Pipe-Delimited)',
                'attributes' => ['size' => 60]
            ],
            'default_values' => [
                'type'       => 'text',
                'label'      => 'Default Field Values (Pipe-Delimited)',
                'attributes' => ['size' => 60]
            ],
            'attributes' => [
                'type'       => 'text',
                'label'      => 'Field Attributes',
                'attributes' => ['size' => 60]
            ]
        ]
    ],
    'Fields\Form\FieldGroup' => [
        [
            'submit' => [
                'type'       => 'submit',
                'value'      => 'Save',
                'attributes' => [
                    'class'  => 'save-btn wide'
                ]
            ],
            'order' => [
                'type'       => 'text',
                'label'      => 'Order',
                'attributes' => ['size' => 3],
                'value'      => 0
            ],
            'dynamic' => [
                'type'  => 'radio',
                'label' => 'Dynamic',
                'value' => [
                    '1' => 'Yes',
                    '0' => 'No'
                ],
                'marked' => 0
            ],
            'id' => [
                'type'  => 'hidden',
                'value' => 0
            ]
        ],
        [
            'name' => [
                'type'       => 'text',
                'label'      => 'Group Name',
                'required'   => true,
                'attributes' => ['size' => 60]
            ]
        ]
    ]
];
