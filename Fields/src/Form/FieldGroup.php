<?php

namespace Fields\Form;

use Pop\Form\Form;
use Pop\Validator;

class FieldGroup extends Form
{

    /**
     * Constructor
     *
     * Instantiate the form object
     *
     * @param  array  $fields
     * @param  string $action
     * @param  string $method
     * @return FieldGroup
     */
    public function __construct(array $fields = null, $action = null, $method = 'post')
    {
        $fields = [
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
        ];

        parent::__construct($fields, $action, $method);

        $this->setAttribute('id', 'field-group-form');
        $this->setIndent('    ');
    }

}