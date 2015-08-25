<?php

return [
    'Phire\Fields\Form\Field' => [
        [
            'submit' => [
                'type'       => 'submit',
                'value'      => 'Save',
                'attributes' => [
                    'class'  => 'save-btn wide'
                ]
            ],
            'prepend' => [
                'type'  => 'select',
                'label' => 'Field Placement',
                'value' => [
                    '0' => 'Append',
                    '1' => 'Prepend'
                ]
            ],
            'group_id' => [
                'type'  => 'select',
                'label' => 'Field Group',
                'value' => ['----' => '----']
            ],
            'order' => [
                'type'       => 'text',
                'label'      => 'Order',
                'attributes' => [
                    'size'  => 3,
                    'class' => 'order-field'
                ],
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
            ]
        ],
        [
            'name' => [
                'type'       => 'text',
                'label'      => 'Field Name',
                'required'   => true,
                'attributes' => [
                    'size'  => 60,
                    'style' => 'width: 99.5%'
                ]
            ],
            'label' => [
                'type'       => 'text',
                'label'      => 'Field Label',
                'attributes' => [
                    'size'  => 60,
                    'style' => 'width: 99.5%'
                ]
            ],
            'values' => [
                'type'       => 'text',
                'label'      => 'Field Values (Pipe-Delimited)',
                'attributes' => [
                    'size'  => 60,
                    'style' => 'width: 99.5%'
                ]
            ],
            'default_values' => [
                'type'       => 'text',
                'label'      => 'Default Field Values (Pipe-Delimited)',
                'attributes' => [
                    'size'  => 60,
                    'style' => 'width: 99.5%'
                ]
            ],
            'attributes' => [
                'type'       => 'text',
                'label'      => 'Field Attributes',
                'attributes' => [
                    'size'  => 60,
                    'style' => 'width: 99.5%'
                ]
            ]
        ],
        [
            'validator_1' => [
                'type'       => 'select',
                'label'      => '<a href="#" onclick="return phire.addValidator();">[+]</a> Field Validators',
                'value'      => [
                    '----'                 => '----',
                    'AlphaNumeric'         => 'AlphaNumeric',
                    'Alpha'                => 'Alpha',
                    'BetweenInclude'       => 'BetweenInclude',
                    'Between'              => 'Between',
                    'CreditCard'           => 'CreditCard',
                    'Email'                => 'Email',
                    'Equal'                => 'Equal',
                    'Excluded'             => 'Excluded',
                    'GreaterThanEqual'     => 'GreaterThanEqual',
                    'GreaterThan'          => 'GreaterThan',
                    'Included'             => 'Included',
                    'Ipv4'                 => 'Ipv4',
                    'Ipv6'                 => 'Ipv6',
                    'IsSubnetOf'           => 'IsSubnetOf',
                    'LengthBetweenInclude' => 'LengthBetweenInclude',
                    'LengthBetween'        => 'LengthBetween',
                    'LengthGte'            => 'LengthGte',
                    'LengthGt'             => 'LengthGt',
                    'LengthLte'            => 'LengthLte',
                    'LengthLt'             => 'LengthLt',
                    'Length'               => 'Length',
                    'LessThanEqual'        => 'LessThanEqual',
                    'LessThan'             => 'LessThan',
                    'NotEmpty'             => 'NotEmpty',
                    'NotEqual'             => 'NotEqual',
                    'Numeric'              => 'Numeric',
                    'RegEx'                => 'RegEx',
                    'Subnet'               => 'Subnet',
                    'Url'                  => 'Url'
                ]
            ],
            'validator_value_1' => [
                'type'       => 'text',
                'attributes' => [
                    'size'        => 20,
                    'placeholder' => 'Value'
                ]
            ],
            'validator_message_1' => [
                'type'       => 'text',
                'attributes' => [
                    'size'        => 40,
                    'placeholder' => 'Message'
                ]
            ]
        ],
        [
            'model_1' => [
                'type'       => 'select',
                'label'      => '<a href="#" onclick="return phire.addModel();">[+]</a> Field Models &amp; Types',
                'value'      => ['----' => '----'],
                'attributes' => [
                    'onchange' => 'phire.getModelTypes(this);'
                ]
            ],
            'model_type_1' => [
                'type'       => 'select',
                'value'      => ['----' => '----']
            ]
        ]
    ],
    'Phire\Fields\Form\FieldGroup' => [
        [
            'submit' => [
                'type'       => 'submit',
                'value'      => 'Save',
                'attributes' => [
                    'class'  => 'save-btn wide'
                ]
            ],
            'prepend' => [
                'type'  => 'select',
                'label' => 'Group Placement',
                'value' => [
                    '0' => 'Append',
                    '1' => 'Prepend'
                ]
            ],
            'order' => [
                'type'       => 'text',
                'label'      => 'Order',
                'attributes' => [
                    'size'  => 3,
                    'class' => 'order-field'
                ],
                'value'      => 0
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
                'attributes' => [
                    'size'  => 60,
                    'style' => 'width: 99.5%'
                ]
            ]
        ]
    ]
];
