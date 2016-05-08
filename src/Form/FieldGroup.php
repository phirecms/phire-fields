<?php

namespace Phire\Fields\Form;

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
    public function __construct(array $fields, $action = null, $method = 'post')
    {
        parent::__construct($fields, $action, $method);
        $this->setAttribute('id', 'field-group-form');
        $this->setIndent('    ');
    }

}