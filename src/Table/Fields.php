<?php

namespace Phire\Fields\Table;

use Pop\Db\Record;

class Fields extends Record
{

    /**
     * Table prefix
     * @var string
     */
    protected $prefix = DB_PREFIX;

    /**
     * Primary keys
     * @var array
     */
    protected $primaryKeys = ['id'];

    /**
     * Method to determine if the field accepts multiple values
     * @return boolean
     */
    public function isMultiple()
    {
        $result = false;

        if (isset($this->type)) {
            $result = (($this->type == 'checkbox') ||
                (($this->type == 'select') && (strpos($this->attributes, 'multiple') !== false)));
        }

        return $result;
    }

}