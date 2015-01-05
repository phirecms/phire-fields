<?php

namespace Fields\Form;

use Fields\Table;
use Pop\Form\Form;
use Pop\Validator;

class Field extends Form
{

    /**
     * Constructor
     *
     * Instantiate the form object
     *
     * @param  array  $models
     * @param  array  $fields
     * @param  string $action
     * @param  string $method
     * @return Field
     */
    public function __construct(array $models, array $fields = null, $action = null, $method = 'post')
    {
        $editors = ['source' => 'Source'];
        if (file_exists(getcwd() . CONTENT_PATH . '/modules/phire/assets/js/ckeditor')) {
            $editors['ckeditor'] = 'CKEditor';
        }
        if (file_exists(getcwd() . CONTENT_PATH . '/modules/phire/assets/js/tinymce')) {
            $editors['tinymce'] = 'TinyMCE';
        }

        $groupValues = ['----' => '----'];
        $modelValues = ['----' => '----'];

        $groups = Table\FieldGroups::findAll();
        foreach ($groups->rows() as $group) {
            $groupValues[$group->id] = $group->name;
        }

        foreach ($models as $model => $type) {
            $modelValues[$model] = $model;
        }

        $fields = [
            [
                'submit' => [
                    'type'       => 'submit',
                    'value'      => 'Save',
                    'attributes' => [
                        'class'  => 'save-btn wide'
                    ]
                ],
                'group_id' => [
                    'type'  => 'select',
                    'label' => 'Field Group',
                    'value' => $groupValues
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
                        'text-history'     => 'text-history',
                        'textarea'         => 'textarea',
                        'textarea-history' => 'textarea-history',
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
                    'value'      => $editors,
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
                ],
                'validator_new_1' => [
                    'type'       => 'select',
                    'label'      => '<a href="#" onclick="phire.addValidator(); return false;">[+]</a> Field Validators',
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
                    ],
                    'attributes' => [
                        'style' => 'display: block; padding: 4px 4px 5px 4px; margin: 0 0 4px 0; height: 28px;'
                    ]
                ],
                'validator_value_new_1' => [
                    'type'       => 'text',
                    'attributes' => [
                        'size'  => 10,
                        'style' => 'display: block; padding: 4px 4px 5px 4px; margin: 0 0 4px 0; height: 17px;'
                    ]
                ],
                'validator_message_new_1' => [
                    'type'       => 'text',
                    'attributes' => [
                        'size'  => 30,
                        'style' => 'display: block; padding: 4px 4px 5px 4px; margin: 0 0 4px 0; height: 17px;'
                    ]
                ],
                'model_new_1' => [
                    'type'       => 'select',
                    'label'      => '<a href="#" onclick="phire.addModel(); return false;">[+]</a> Field Model',
                    'value'      => $modelValues,
                    'attributes' => [
                        'style'    => 'display: block; padding: 4px 4px 5px 4px; margin: 0 0 4px 0; height: 28px;',
                        'onchange' => 'phire.getModelTypes(this, \'' . BASE_PATH . APP_URI . '\');'
                    ]
                ],
                'model_type_new_1' => [
                    'type'       => 'select',
                    'value'      => ['----' => '----'],
                    'attributes' => [
                        'style' => 'display: block; padding: 4px 4px 5px 4px; margin: 0 0 4px 0; height: 28px;'
                    ]
                ]
            ]
        ];

        parent::__construct($fields, $action, $method);

        $this->setAttribute('id', 'field-form');
        $this->setIndent('    ');
    }

}