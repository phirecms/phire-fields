<?php
/**
 * Phire Fields Module
 *
 * @link       https://github.com/phirecms/phire-fields
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.phirecms.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Phire\Fields\Model;

use Phire\Fields\Table;
use Phire\Model\AbstractModel;
use Pop\Crypt\Mcrypt;
use Pop\Db\Record;
use Pop\Db\Sql;
use Pop\Web\Session;

/**
 * Field Value Model class
 *
 * @category   Phire\Fields
 * @package    Phire\Fields
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.phirecms.org/license     New BSD License
 * @version    1.0.0
 */
class FieldValue extends AbstractModel
{

    /**
     * Save field values
     *
     * @param  array  $fields
     * @param  int    $modelId
     * @param  string $model
     * @return array
     */
    public function save(array $fields, $modelId, $model)
    {
        $values = [];

        foreach ($fields as $name => $value) {
            if (substr($name, 0, 6) == 'field_') {
                $fieldId = substr($name, (strpos($name, '_') + 1));
                $field   = Table\Fields::findById($fieldId);
                if (isset($field->id)) {
                    if ($field->storage == 'eav') {
                        if ($field->encrypt) {
                            if (is_array($value)) {
                                foreach ($value as $k => $v) {
                                    $value[$k] = (new Mcrypt())->create($v);
                                }
                            } else {
                                $value = (new Mcrypt())->create($value);
                            }
                        }
                        $fv = new Table\FieldValues([
                            'field_id'  => $fieldId,
                            'model_id'  => $modelId,
                            'model'     => $model,
                            'value'     => json_encode($value),
                            'timestamp' => time()
                        ]);
                        $fv->save();
                    } else {
                        if (!is_array($value)) {
                            $value = [$value];
                        }
                        foreach ($value as $v) {
                            if ($field->encrypt) {
                                $v = (new Mcrypt())->create($v);
                            }
                            $fv = new Record([
                                'model_id'  => $modelId,
                                'model'     => $model,
                                'timestamp' => time(),
                                'revision'  => 0,
                                'value'     => $v
                            ]);
                            $fv->setPrefix(DB_PREFIX)
                                ->setPrimaryKeys(['id'])
                                ->setTable('field_' . $field->name);

                            $fv->save();
                        }
                    }

                    $values[$field->name] = $value;
                }
            }
        }

        return $values;
    }

    /**
     * Get all model objects with dynamic field values
     *
     * @param  mixed  $class
     * @param  array  $params
     * @param  string $method
     * @param  array $filters
     * @throws \Exception
     * @return mixed
     */
    public static function getModelObjects($class, array $params = [], $method = 'getAll', array $filters = [])
    {
        if (is_array($class)) {
            $rows  = $class;
        } else {
            $model = new $class();

            if (!($model instanceof \Phire\Model\AbstractModel) || !method_exists($model, $method)) {
                throw new \Exception(
                    'Error: The model class must be an instance of Phire\Model\AbstractModel and have the \'' . $method . '\' method.'
                );
            }

            $reflect      = new \ReflectionMethod($class, $method);
            $methodParams = $reflect->getParameters();
            $realParams   = [];
            foreach ($methodParams as $param) {
                $realParams[$param->name] = (isset($params[$param->name]) ? $params[$param->name] : null);
            }

            $rows = call_user_func_array([$model, $method], $realParams);
        }

        foreach ($rows as $row) {
            $sql   = Table\Fields::sql();
            $sql->select()->where('models LIKE :models');

            $value  = ($sql->getDbType() == \Pop\Db\Sql::SQLITE) ? '%' . $class . '%' : '%' . addslashes($class) . '%';
            $fields = Table\Fields::execute((string)$sql, ['models' => $value]);

            if (isset($row->id) && ($fields->count() > 0)) {
                foreach ($fields->rows() as $field) {
                    $fValue = '';
                    if ($field->storage == 'eav') {
                        $fv = Table\FieldValues::findById([$field->id, $row->id, $class]);
                        if (isset($fv->field_id)) {
                            $fValue = json_decode($fv->value);
                            foreach ($filters as $filter => $params) {
                                if ((null !== $params) && count($params) > 0) {
                                    $params = array_merge([$fValue], $params);
                                } else {
                                    $params = [$fValue];
                                }
                                $fValue = call_user_func_array($filter, $params);
                            }
                            if ($field->encrypt) {
                                if (is_array($fValue)) {
                                    foreach ($fValue as $k => $fv) {
                                        $fValue =(new Mcrypt())->decrypt($fValue);
                                    }
                                } else {
                                    $fValue = (new Mcrypt())->decrypt($fValue);
                                }
                            }
                        }
                    } else {
                        $fv = new Record();
                        $fv->setPrefix(DB_PREFIX)
                            ->setPrimaryKeys(['id'])
                            ->setTable('field_' . $field->name);

                        $fv->findRecordsBy([
                            'model_id' => $row->id,
                            'model'    => $class
                        ]);

                        if ($fv->hasRows()) {
                            if ($fv->count() > 1) {
                                $fValue = [];
                                foreach ($fv->rows() as $f) {
                                    $fValue[] = ($field->encrypt) ? (new Mcrypt())->decrypt($f->value) : $f->value;
                                }
                            } else {
                                $fValue = ($field->encrypt) ? (new Mcrypt())->decrypt($fv->value) : $fv->value;
                            }
                        }
                    }
                    $row->{$field->name} = self::parse($fValue);
                }
            }
        }

        return $rows;
    }

    /**
     * Get single model object with dynamic field values
     *
     * @param  mixed  $class
     * @param  array  $params
     * @param  string $method
     * @param  array $filters
     * @throws \Exception
     * @return mixed
     */
    public static function getModelObject($class, array $params = [], $method = 'getById', array $filters = [])
    {
        if ($class instanceof \Phire\Model\AbstractModel) {
            $model = $class;
        } else {
            $model = new $class();

            if (!($model instanceof \Phire\Model\AbstractModel) || !method_exists($model, $method)) {
                throw new \Exception(
                    'Error: The model class must be an instance of Phire\Model\AbstractModel and have the \'' . $method . '\' method.'
                );
            }

            call_user_func_array([$model, $method], $params);
        }

        if (isset($model->id)) {
            $model = self::getModelObjectValues($model, null, $filters);
        }

        return $model;
    }

    /**
     * Get single model object with dynamic field values using a table join
     *
     * @param  string $table
     * @param  string $model
     * @param  array  $params
     * @param  array  $filters
     * @param  array  $conds
     * @param  string $order
     * @return mixed
     */
    public static function getModelObjectsFromTable(
        $table, $model, array $params = [], array $filters = [], array $conds = [], $order = null
    )
    {
        $sql = Table\Fields::sql();
        $sql->select()->where('models LIKE :models');

        $value     = ($sql->getDbType() == \Pop\Db\Sql::SQLITE) ? '%' . $model . '%' : '%' . addslashes($model) . '%';
        $fields    = Table\Fields::execute((string)$sql, ['models' => $value]);
        $encrypted = [];
        $multiples = [];

        $allTables = $sql->db()->getTables();

        if ($fields->hasRows()) {
            $sql = new Sql($sql->db(), $table);
            $select = [$table . '.*'];
            foreach ($fields->rows() as $field) {
                $field->models = unserialize($field->models);
                if (self::isFieldAllowed($field->models, $params) && in_array(DB_PREFIX . 'field_' . $field->name, $allTables)) {
                    $select[$field->name] = DB_PREFIX . 'field_' . $field->name . '.value';
                }
            }

            $sql->select($select);
            foreach ($fields->rows() as $field) {
                if (self::isFieldAllowed($field->models, $params) && in_array(DB_PREFIX . 'field_' . $field->name, $allTables)) {
                    if ($field->encrypt) {
                        $encrypted[$field->id] = $field->name;
                    }
                    if (($field->type != 'textarea-history') && (($field->dynamic) || ($field->type == 'checkbox') ||
                            (($field->type == 'select') && (strpos($field->attributes, 'multiple') !== false)))
                    ) {
                        $multiples[$field->id] = $field->name;
                    }
                    $sql->select()->join(DB_PREFIX . 'field_' . $field->name, [$table . '.id' => DB_PREFIX . 'field_' . $field->name . '.model_id']);
                }
            }

            foreach ($fields->rows() as $field) {
                if (self::isFieldAllowed($field->models, $params) && in_array(DB_PREFIX . 'field_' . $field->name, $allTables)) {
                    $sql->select()->where(DB_PREFIX . 'field_' . $field->name . '.revision = 0');
                }
            }

            if (count($params) > 0) {
                foreach ($params as $param) {
                    $sql->select()->where($table . '.' . $param);
                }
            }

            if (count($conds) > 0) {
                foreach ($conds as $name => $cond) {
                    $sql->select()->where(DB_PREFIX . 'field_' . $name . '.value ' . $cond);
                }
            }

            if (null !== $order) {
                $orderAry = explode(' ', $order);
                $sql->select()->orderBy($orderAry[0], $orderAry[1]);
            }

            $record = new Record();
            $record->setPrefix(DB_PREFIX)
                ->setPrimaryKeys(['id'])
                ->setTable($table);

            $record->executeQuery($sql, Record::ROW_AS_ARRAYOBJECT);

            $values = $record->rows();

            foreach ($values as $key => $value) {
                foreach ($value as $k => $v) {
                    foreach ($filters as $filter => $params) {
                        if ((null !== $params) && count($params) > 0) {
                            $params = array_merge([$v], $params);
                        } else {
                            $params = [$v];
                        }
                        $v = call_user_func_array($filter, $params);
                    }
                    if (in_array($key, $encrypted)) {
                        $values[$key][$k] = self::parse((new Mcrypt())->decrypt($v));
                    } else {
                        $values[$key][$k] = self::parse($v);
                    }
                }
            }

            if (count($multiples) > 0) {
                foreach ($values as $i => $row) {
                    foreach ($multiples as $id => $name) {
                        $fv = new Record();
                        $fv->setPrefix(DB_PREFIX)
                            ->setPrimaryKeys(['id'])
                            ->setTable('field_' . $name);

                        $fv->findRecordsBy(['model_id' => $row->id, 'model' => $model]);
                        if ($fv->hasRows()) {
                            $values[$i][$name] = [];
                            foreach ($fv->rows() as $f) {
                                $values[$i][$name][] = self::parse(((in_array($id, $encrypted)) ? (new Mcrypt())->decrypt($f->value) : $f->value));
                            }
                        }
                    }
                }
            }
        } else {
            $sql = new Sql($sql->db(), $table);
            $sql->select([$table . '.*']);

            $record = new Record();
            $record->setPrefix(DB_PREFIX)
                ->setPrimaryKeys(['id'])
                ->setTable($table);

            $record->executeQuery($sql, Record::ROW_AS_ARRAYOBJECT);

            $values = $record->rows();
        }

        $filteredValues = [];

        foreach ($values as $value) {
            if (!in_array($value, $filteredValues)) {
                $filteredValues[] = $value;
            }
        }

        return $filteredValues;
    }

    /**
     * Get single model object with dynamic field values using a table join
     *
     * @param  string $table
     * @param  string $model
     * @param  int    $modelId
     * @param  array  $filters
     * @return mixed
     */
    public static function getModelObjectFromTable($table, $model, $modelId, array $filters = [])
    {
        $sql = Table\Fields::sql();
        $sql->select()->where('models LIKE :models');

        $value     = ($sql->getDbType() == \Pop\Db\Sql::SQLITE) ? '%' . $model . '%' : '%' . addslashes($model) . '%';
        $fields    = Table\Fields::execute((string)$sql, ['models' => $value], Record::ROW_AS_ARRAYOBJECT);
        $encrypted = [];
        $multiples = [];

        if ($fields->hasRows()) {
            $sql = new Sql($sql->db(), $table);
            $select = [$table . '.*'];
            $where  = [];
            foreach ($fields->rows() as $field) {
                $select[$field->name] = DB_PREFIX . 'field_' . $field->name . '.value';
            }

            $sql->select($select);
            foreach ($fields->rows() as $field) {
                if ($field->encrypt) {
                    $encrypted[$field->id] = $field->name;
                }
                if (($field->type != 'textarea-history') && (($field->dynamic) || ($field->type == 'checkbox') ||
                        (($field->type == 'select') && (strpos($field->attributes, 'multiple') !== false)))) {
                    $multiples[$field->id] = $field->name;
                }
                $sql->select()->join(DB_PREFIX . 'field_' . $field->name, [$table . '.id' => DB_PREFIX . 'field_' . $field->name . '.model_id']);
            }

            $sql->select()->where($table . '.id = :id');
            if (count($where) > 0) {
                foreach ($where as $w) {
                    $sql->select()->where($w);
                }
            }

            $record = new Record();
            $record->setPrefix(DB_PREFIX)
                ->setPrimaryKeys(['id'])
                ->setTable($table);

            $record->executeStatement($sql, [$table . '.id' => $modelId], Record::ROW_AS_ARRAYOBJECT);

            $values = $record->getColumns();

            foreach ($values as $key => $value) {
                foreach ($filters as $filter => $params) {
                    if ((null !== $params) && count($params) > 0) {
                        $params = array_merge([$value], $params);
                    } else {
                        $params = [$value];
                    }
                    $value = call_user_func_array($filter, $params);
                }
                if (in_array($key, $encrypted)) {
                    $values[$key] = self::parse((new Mcrypt())->decrypt($value));
                } else {
                    $values[$key] = self::parse($value);
                }
            }

            if (count($multiples) > 0) {
                foreach ($multiples as $id => $name) {
                    $fv = new Record();
                    $fv->setPrefix(DB_PREFIX)
                        ->setPrimaryKeys(['id'])
                        ->setTable('field_' . $name);

                    $fv->findRecordsBy(['model_id' => $modelId, 'model' => $model], null, Record::ROW_AS_ARRAYOBJECT);
                    if ($fv->hasRows()) {
                        $values[$name] = [];
                        foreach ($fv->rows() as $f) {
                            $values[$name][] = self::parse(((in_array($id, $encrypted)) ? (new Mcrypt())->decrypt($f->value) : $f->value));
                        }
                    }
                }
            }
        } else {
            $sql = new Sql($sql->db(), $table);
            $sql->select([$table . '.*'])->where($table . '.id = :id');

            $record = new Record();
            $record->setPrefix(DB_PREFIX)
                ->setPrimaryKeys(['id'])
                ->setTable($table);

            $record->executeStatement($sql, [$table . '.id' => $modelId], Record::ROW_AS_ARRAYOBJECT);

            $values = $record->getColumns();
        }

        $values['id'] = $modelId;
        return new $model($values);
    }

    /**
     * Get field values for a model object
     *
     * @param  mixed $model
     * @param  int   $id
     * @param  array $filters
     * @return mixed
     */
    public static function getModelObjectValues($model, $id = null, array $filters = [])
    {
        if (is_string($model)) {
            $class = $model;
        } else {
            $class = get_class($model);
            if (isset($model->id)) {
                $id = $model->id;
            }
        }

        $fieldValues = [];

        $sql = Table\Fields::sql();
        $sql->select()->where('models LIKE :models');

        $value  = ($sql->getDbType() == \Pop\Db\Sql::SQLITE) ? '%' . $class . '%' : '%' . addslashes($class) . '%';
        $fields = Table\Fields::execute((string)$sql, ['models' => $value]);

        if ((null !== $id) && ($fields->count() > 0)) {
            foreach ($fields->rows() as $field) {
                $fValue = '';

                if ($field->storage == 'eav') {
                    $fv = Table\FieldValues::findById([$field->id, $id, $class]);
                    if (isset($fv->field_id)) {
                        $fValue = json_decode($fv->value);
                        foreach ($filters as $filter => $params) {
                            if ((null !== $params) && count($params) > 0) {
                                $params = array_merge([$fValue], $params);
                            } else {
                                $params = [$fValue];
                            }
                            $fValue = call_user_func_array($filter, $params);
                        }
                        if ($field->encrypt) {
                            if (is_array($fValue)) {
                                foreach ($fValue as $k => $fv) {
                                    $fValue =(new Mcrypt())->decrypt($fValue);
                                }
                            } else {
                                $fValue = (new Mcrypt())->decrypt($fValue);
                            }
                        }
                    }
                } else {
                    $fv = new Record();
                    $fv->setPrefix(DB_PREFIX)
                        ->setPrimaryKeys(['id'])
                        ->setTable('field_' . $field->name);

                    $fv->findRecordsBy([
                        'model_id' => $id,
                        'model'    => $class,
                        'revision' => 0
                    ]);

                    if ($fv->hasRows()) {
                        if ($fv->count() > 1) {
                            $fValue = [];
                            foreach ($fv->rows() as $f) {
                                $fValue[] = ($field->encrypt) ? (new Mcrypt())->decrypt($f->value) : $f->value;
                            }
                        } else {
                            $fValue = ($field->encrypt) ? (new Mcrypt())->decrypt($fv->value) : $fv->value;
                        }
                    }
                }

                if (is_object($model)) {
                    $model->{$field->name} = self::parse($fValue);
                } else {
                    $fieldValues[$field->name] = self::parse($fValue);
                }
            }
        }

        return (is_object($model)) ? $model : $fieldValues;
    }

    /**
     * Parse the value
     *
     * @param  mixed $fieldValue
     * @return boolean
     */
    public static function parse($fieldValue)
    {
        if (is_array($fieldValue)) {
            foreach ($fieldValue as $key => $value) {
                $fieldValue[$key] = self::parseValue($value);
            }
            return $fieldValue;
        } else {
            return self::parseValue($fieldValue);
        }
    }

    /**
     * Determine if the field is allowed for the entity type
     *
     * @param  array $models
     * @param  array $params
     * @return boolean
     */
    public static function isFieldAllowed($models, $params)
    {
        $result = false;
        foreach ($models as $model) {
            if (!empty($model['type_field']) && !empty($model['type_value']) &&
                in_array($model['type_field'] . ' = ' . $model['type_value'], $params)) {
                $result = true;
            } else if (empty($model['type_field']) && empty($model['type_value'])) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Parse the value
     *
     * @param  string $fieldValue
     * @return boolean
     */
    protected static function parseValue($fieldValue)
    {
        $fieldValue = str_replace(
            ['[{base_path}]', '[{content_path}]'],
            [BASE_PATH, CONTENT_PATH],
            $fieldValue
        );

        // Parse any date placeholders
        $dates = [];
        preg_match_all('/\[\{date.*\}\]/', $fieldValue, $dates);
        if (isset($dates[0]) && isset($dates[0][0])) {
            foreach ($dates[0] as $date) {
                $pattern  = str_replace('}]', '', substr($date, (strpos($date, '_') + 1)));
                $fieldValue = str_replace($date, date($pattern), $fieldValue);
            }
        }

        // Parse any session placeholders
        $open  = [];
        $close = [];
        $merge = [];
        $sess  = [];
        preg_match_all('/\[\{sess\}\]/msi', $fieldValue, $open, PREG_OFFSET_CAPTURE);
        preg_match_all('/\[\{\/sess\}\]/msi', $fieldValue, $close, PREG_OFFSET_CAPTURE);

        // If matches are found, format and merge the results.
        if ((isset($open[0][0])) && (isset($close[0][0]))) {
            foreach ($open[0] as $key => $value) {
                $merge[] = [$open[0][$key][0] => $open[0][$key][1], $close[0][$key][0] => $close[0][$key][1]];
            }
        }
        foreach ($merge as $match) {
            $sess[] = substr($fieldValue, $match['[{sess}]'], (($match['[{/sess}]'] - $match['[{sess}]']) + 9));
        }

        if (count($sess) > 0) {
            $session = Session::getInstance();
            foreach ($sess as $s) {
                $sessString = str_replace(['[{sess}]', '[{/sess}]'], ['', ''], $s);
                $isSess = null;
                $noSess = null;
                if (strpos($sessString, '[{or}]') !== false) {
                    $sessValues = explode('[{or}]', $sessString);
                    if (isset($sessValues[0])) {
                        $isSess = $sessValues[0];
                    }
                    if (isset($sessValues[1])) {
                        $noSess = $sessValues[1];
                    }
                } else {
                    $isSess = $sessString;
                }
                if (null !== $isSess) {
                    if (!isset($session->user)) {
                        $fieldValue = str_replace($s, $noSess, $fieldValue);
                    } else {
                        $newSess = $isSess;
                        foreach ($_SESSION as $sessKey => $sessValue) {
                            if ((is_array($sessValue) || ($sessValue instanceof \ArrayObject)) &&
                                (strpos($fieldValue, '[{' . $sessKey . '->') !== false)) {
                                foreach ($sessValue as $sessK => $sessV) {
                                    if (!is_array($sessV)) {
                                        $newSess = str_replace('[{' . $sessKey . '->' . $sessK . '}]', $sessV, $newSess);
                                    }
                                }
                            } else if (!is_array($sessValue) && !($sessValue instanceof \ArrayObject) &&
                                (strpos($fieldValue, '[{' . $sessKey) !== false)) {
                                $newSess = str_replace('[{' . $sessKey . '}]', $sessValue, $newSess);
                            }
                        }
                        if ($newSess != $isSess) {
                            $fieldValue = str_replace('[{sess}]' . $sessString . '[{/sess}]', $newSess, $fieldValue);
                        } else {
                            $fieldValue = str_replace($s, $noSess, $fieldValue);
                        }
                    }
                } else {
                    $fieldValue = str_replace($s, '', $fieldValue);
                }
            }
        }

        return $fieldValue;
    }

}
