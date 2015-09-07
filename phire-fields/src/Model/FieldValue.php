<?php

namespace Phire\Fields\Model;

use Phire\Fields\Table;
use Phire\Model\AbstractModel;

class FieldValue extends AbstractModel
{

    /**
     * Save field values
     *
     * @param  array  $fields
     * @param  int    $modelId
     * @return array
     */
    public function save(array $fields, $modelId)
    {
        $values = [];

        foreach ($fields as $name => $value) {
            if (substr($name, 0, 6) == 'field_') {
                $fieldId = substr($name, (strpos($name, '_') + 1));
                $fv      = new Table\FieldValues([
                    'field_id'  => $fieldId,
                    'model_id'  => $modelId,
                    'value'     => json_encode($value),
                    'timestamp' => time()
                ]);
                $fv->save();

                $fld = Table\Fields::findById($fieldId);
                if (isseT($fld->id)) {
                    $values[$fld->name] = $value;
                }
            }
        }

        return $values;
    }

    /**
     * Get all model objects with dynamic field values
     *
     * @param  string $class
     * @param  array  $params
     * @param  string $method
     * @param  array $filters
     * @throws \Exception
     * @return mixed
     */
    public static function getModelObjects($class, array $params = [], $method = 'getAll', array $filters = [])
    {
        $model = new $class();

        if (!($model instanceof \Phire\Model\AbstractModel) || !method_exists($model, $method)) {
            throw new \Exception(
                'Error: The model class must be an instance of Phire\Model\AbstractModel and have the \'' . $method .  '\' method.'
            );
        }

        $reflect      = new \ReflectionMethod($class, $method);
        $methodParams = $reflect->getParameters();
        $realParams   = [];
        foreach ($methodParams as $param) {
            $realParams[$param->name] = (isset($params[$param->name]) ? $params[$param->name] : null);
        }

        $rows = call_user_func_array([$model, $method], $realParams);

        foreach ($rows as $row) {
            $sql   = Table\Fields::sql();
            $sql->select()->where('models LIKE :models');

            $value = ($sql->getDbType() == \Pop\Db\Sql::SQLITE) ? '%' . $class . '%' : '%' . addslashes($class) . '%';

            $fields = Table\Fields::execute((string)$sql, ['models' => $value]);
            if (isset($row->id) && ($fields->count() > 0)) {
                foreach ($fields->rows() as $field) {
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
                        $row->{$field->name} = $fValue;
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * Get single model object with dynamic field values
     *
     * @param  string $class
     * @param  array  $params
     * @param  string $method
     * @param  array $filters
     * @throws \Exception
     * @return mixed
     */
    public static function getModelObject($class, array $params, $method = 'getById', array $filters = [])
    {
        $model = new $class();

        if (!($model instanceof \Phire\Model\AbstractModel) || !method_exists($model, $method)) {
            throw new \Exception(
                'Error: The model class must be an instance of Phire\Model\AbstractModel and have the \'' . $method .  '\' method.'
            );
        }

        call_user_func_array([$model, $method], $params);

        if (isset($model->id)) {
            $model = self::getModelObjectValues($model, null, $filters);
        }

        return $model;
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
                    if (is_object($model)) {
                        $model->{$field->name} = $fValue;
                    } else {
                        $fieldValues[$field->name] = $fValue;
                    }
                }
            }
        }

        return (is_object($model)) ? $model : $fieldValues;
    }

}
