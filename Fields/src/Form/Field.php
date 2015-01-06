<?php

namespace Fields\Form;

use Fields\Table;
use Pop\Form\Form;
use Pop\Validator;

class Field extends Form
{

    /**
     * Available validators
     * @var array
     */
    protected $validators = [
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
    ];

    /**
     * Constructor
     *
     * Instantiate the form object
     *
     * @param  array  $models
     * @param  array  $validators
     * @param  array  $fieldModels
     * @param  array  $fields
     * @param  string $action
     * @param  string $method
     * @return Field
     */
    public function __construct(
        array $models, array $validators = [], array $fieldModels = [], array $fields = null, $action = null, $method = 'post'
    )
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

        $fields[0]['group_id']['value'] = $groupValues;
        $fields[1]['editor']['value']   = $editors;
        $fields[1]['validator_new_1']   = [
            'type'       => 'select',
            'label'      => '<a href="#" onclick="phire.addValidator(); return false;">[+]</a> Field Validators',
            'value'      => $this->validators,
            'attributes' => [
                'style' => 'display: block; padding: 4px 4px 5px 4px; margin: 0 0 4px 0; height: 28px; min-width: 200px;'
            ]
        ];
        $fields[1]['validator_value_new_1'] = [
            'type'       => 'text',
            'attributes' => [
                'size'  => 20,
                'style' => 'display: block; padding: 4px 4px 5px 4px; margin: 0 0 4px 0; height: 17px; min-width: 168px;'
            ]
        ];
        $fields[1]['validator_message_new_1'] = [
            'type'       => 'text',
            'attributes' => [
                'size'  => 40,
                'style' => 'display: block; padding: 4px 4px 5px 4px; margin: 0 0 4px 0; height: 17px;'
            ]
        ];

        foreach ($validators as $key => $validator) {
            $fields[1]['validator_cur_' . ($key + 1)] = [
                'type'       => 'select',
                'label'      => '&nbsp;',
                'value'      => $this->validators,
                'attributes' => [
                    'style' => 'display: block; padding: 4px 4px 5px 4px; margin: 0 0 4px 0; height: 28px; min-width: 200px;'
                ],
                'marked'     => $validator['validator']
            ];
            $fields[1]['validator_value_cur_' . ($key + 1)] = [
                'type'       => 'text',
                'attributes' => [
                    'size'  => 20,
                    'style' => 'display: block; padding: 4px 4px 5px 4px; margin: 0 0 4px 0; height: 17px; min-width: 168px;'
                ],
                'value'      => $validator['value']
            ];
            $fields[1]['validator_message_cur_' . ($key + 1)] = [
                'type'       => 'text',
                'attributes' => [
                    'size'  => 40,
                    'style' => 'display: block; padding: 4px 4px 5px 4px; margin: 0 0 4px 0; height: 17px;'
                ],
                'value'      => $validator['message']
            ];
            $fields[1]['rm_validators_' . ($key + 1)] = [
                'type'  => 'checkbox',
                'value' => [$key + 1 => '&nbsp;']
            ];
        }

        $fields[1]['model_new_1'] = [
            'type'       => 'select',
            'label'      => '<a href="#" onclick="phire.addModel(); return false;">[+]</a> Field Models &amp; Types',
            'value'      => $modelValues,
            'attributes' => [
                'style'    => 'display: block; padding: 4px 4px 5px 4px; margin: 0 0 4px 0; height: 28px; min-width: 200px;',
                'onchange' => 'phire.getModelTypes(this, \'' . BASE_PATH . APP_URI . '\', false);'
            ]
        ];
        $fields[1]['model_type_new_1'] = [
            'type'       => 'select',
            'value'      => ['----' => '----'],
            'attributes' => [
                'style' => 'display: block; padding: 4px 4px 5px 4px; margin: 0 0 4px 0; height: 28px; min-width: 180px;'
            ]
        ];

        foreach ($fieldModels as $key => $fieldModel) {
            $fields[1]['model_cur_' . ($key + 1)] = [
                'type'       => 'select',
                'label'      => '&nbsp;',
                'value'      => $modelValues,
                'attributes' => [
                    'style'    => 'display: block; padding: 4px 4px 5px 4px; margin: 0 0 4px 0; height: 28px; min-width: 200px;',
                    'onchange' => 'phire.getModelTypes(this, \'' . BASE_PATH . APP_URI . '\', true);'
                ],
                'marked'     => $fieldModel['model']
            ];

            $fieldModelValues = ['----' => '----'];
            if (isset($models[$fieldModel['model']])) {
                foreach ($models[$fieldModel['model']] as $m) {
                    $fieldModelValues[$m['type_field'] . '|' . $m['type_value']] = $m['type_name'];
                }
            }

            $fields[1]['model_type_cur_' . ($key + 1)] = [
                'type'       => 'select',
                'value'      => $fieldModelValues,
                'attributes' => [
                    'style' => 'display: block; padding: 4px 4px 5px 4px; margin: 0 0 4px 0; height: 28px; min-width: 180px;'
                ],
                'marked'     => ((null != $fieldModel['type_field']) && (null != $fieldModel['type_value'])) ?
                    $fieldModel['type_field'] . '|' . $fieldModel['type_value'] : null
            ];
            $fields[1]['rm_models_' . ($key + 1)] = [
                'type'  => 'checkbox',
                'value' => [$key + 1 => '&nbsp;']
            ];
        }

        parent::__construct($fields, $action, $method);
        $this->setAttribute('id', 'field-form');
        $this->setIndent('    ');
    }

}