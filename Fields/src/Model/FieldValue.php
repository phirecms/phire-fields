<?php

namespace Fields\Model;

use Fields\Table;
use Phire\Model\AbstractModel;

class FieldValue extends AbstractModel
{

    /**
     * Get all model objects with dynamic field values
     *
     * @param  string $class
     * @param  array  $params
     * @param  string $method
     * @throws \Exception
     * @return mixed
     */
    public static function getModelObjects($class, array $params = [], $method = 'getAll')
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
                    $fv = Table\FieldValues::findById([$field->id, $row->id]);
                    if (isset($fv->field_id)) {
                        $row->{$field->name} = json_decode($fv->value);
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
     * @throws \Exception
     * @return mixed
     */
    public static function getModelObject($class, array $params, $method = 'getById')
    {
        $model = new $class();

        if (!($model instanceof \Phire\Model\AbstractModel) || !method_exists($model, $method)) {
            throw new \Exception(
                'Error: The model class must be an instance of Phire\Model\AbstractModel and have the \'' . $method .  '\' method.'
            );
        }

        call_user_func_array([$model, $method], $params);

        if (isset($model->id)) {
            $model = self::getModelObjectValues($model);
        }

        return $model;
    }

    /**
     * Get field values for a model object
     *
     * @param  mixed $model
     * @return mixed
     */
    public static function getModelObjectValues($model)
    {
        $class = get_class($model);
        $sql   = Table\Fields::sql();
        $sql->select()->where('models LIKE :models');

        $value = ($sql->getDbType() == \Pop\Db\Sql::SQLITE) ? '%' . $class . '%' : '%' . addslashes($class) . '%';

        $fields = Table\Fields::execute((string)$sql, ['models' => $value]);

        if (isset($model->id) && ($fields->count() > 0)) {
            foreach ($fields->rows() as $field) {
                $fv = Table\FieldValues::findById([$field->id, $model->id]);
                if (isset($fv->field_id)) {
                    $model->{$field->name} = json_decode($fv->value);
                }
            }
        }

        return $model;
    }

}
