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
     * @param  array  $fields
     * @param  string $action
     * @param  string $method
     * @return Field
     */
    public function __construct(array $fields = null, $action = null, $method = 'post')
    {
        parent::__construct($fields, $action, $method);
        $this->setAttribute('id', 'field-form');
        $this->setIndent('    ');
    }

}