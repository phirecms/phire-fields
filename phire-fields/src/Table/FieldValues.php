<?php

namespace Phire\Fields\Table;

use Pop\Db\Record;

class FieldValues extends Record
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
    protected $primaryKeys = ['field_id', 'model_id'];

}