<?php

namespace Fields\Form;

use Pop\Form\Form;
use Pop\Validator;

class Field extends Form
{

    /**
     * Constructor
     *
     * Instantiate the form object
     *
     * @param  array  $fields
     * @param  string $action
     * @param  string $method
     * @return Field
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
                'id' => [
                    'type'  => 'hidden',
                    'value' => 0
                ]
            ],
            [
                'name' => [
                    'type'       => 'text',
                    'label'      => 'Field Name',
                    'attributes' => ['size' => 40]
                ]
            ]
        ];

        parent::__construct($fields, $action, $method);

        $this->setAttribute('id', 'field-form');
        $this->setIndent('    ');
    }

}