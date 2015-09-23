<?php

namespace Phire\Fields\Model;

use Phire\Fields\Table;
use Phire\Model\AbstractModel;
use Pop\Web\Session;

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
                if (isset($fld->id)) {
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
                        $row->{$field->name} = self::parse($fValue);
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
                        $model->{$field->name} = self::parse($fValue);
                    } else {
                        $fieldValues[$field->name] = self::parse($fValue);
                    }
                }
            }
        }

        return (is_object($model)) ? $model : $fieldValues;
    }



    /**
     * Parse the value
     *
     * @param  string $fieldValue
     * @return boolean
     */
    public static function parse($fieldValue)
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
