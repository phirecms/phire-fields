<?php

namespace Phire\Fields\Table;

use Pop\Db\Record;

class FieldGroups extends Record
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

}