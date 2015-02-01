<?php

namespace Fields\Model;

use Fields\Table;
use Pop\Crypt\Mcrypt;
use Pop\File\Upload;
use Phire\Model\AbstractModel;

class FieldValue extends AbstractModel
{


    /**
     * Get all model objects with dynamic field values
     *
     * @param  string $class
     * @param  array  $params
     * @throws \Exception
     * @return mixed
     */
    public static function getAllModelObjects($class, array $params = [])
    {
        $model = new $class();
        if (!($model instanceof \Phire\Model\AbstractModel) || !method_exists($model, 'getAll')) {
            throw new \Exception(
                'Error: The model class must be an instance of Phire\Model\AbstractModel and have the getAll method.'
            );
        }

        $method       = new \ReflectionMethod($class, 'getAll');
        $methodParams = $method->getParameters();
        $realParams   = [];
        foreach ($methodParams as $param) {
            $realParams[$param->name] = (isset($params[$param->name]) ? $params[$param->name] : null);
        }

        $rows = call_user_func_array([$model, 'getAll'], $realParams);

        foreach ($rows as $row) {
            $sql   = Table\Fields::sql();
            $sql->select()->where('models LIKE %' . addslashes($class) . '%');

            $fields = Table\Fields::query((string)$sql);
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
     * Get model object with dynamic field values
     *
     * @param  string $class
     * @param  int    $id
     * @throws \Exception
     * @return mixed
     */
    public static function getModelObjectById($class, $id)
    {
        $model = new $class();

        if (!($model instanceof \Phire\Model\AbstractModel) || !method_exists($model, 'getById')) {
            throw new \Exception(
                'Error: The model class must be an instance of Phire\Model\AbstractModel and have the getById method.'
            );
        }

        $model->getById($id);

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
        $sql->select()->where('models LIKE %' . addslashes($class) . '%');

        $fields = Table\Fields::query((string)$sql);
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

    /**
     * Get all dynamic field values for the form object
     *
     * @param  \Phire\Controller\AbstractController $controller
     * @return void
     */
    public static function getAll(\Phire\Controller\AbstractController $controller)
    {
        if ((!$_POST) && ($controller->hasView()) && (null !== $controller->view()->form) &&
            ((int)$controller->view()->form->id != 0) && (null !== $controller->view()->form) &&
            ($controller->view()->form instanceof \Pop\Form\Form)) {
            $fields  = $controller->view()->form->getFields();
            $modelId = $controller->view()->form->id;
            foreach ($fields as $key => $value) {
                if (substr($key, 0, 6) == 'field_') {
                    $fieldId = (int)substr($key, 6);
                    $field   = Table\Fields::findById($fieldId);
                    if (isset($field->id)) {
                        $fv = Table\FieldValues::findById([$fieldId, $modelId]);
                        if (isset($fv->field_id)) {
                            $fieldValue = $fv->getColumns();
                            $value = json_decode($fieldValue['value']);
                            if ($field->encrypt) {
                                $value = (new Mcrypt())->decrypt($value);
                            }
                            if ($field->type == 'file') {
                                $label = $controller->view()->form->getElement($key)->getLabel() .
                                    ' [ <a href="' . BASE_PATH . CONTENT_PATH . '/assets/fields/files/' .
                                    $value . '" target="_blank">' . $value . '</a> ]';
                                $controller->view()->form->getElement($key)->setLabel($label);
                                $rmCheckbox = new \Pop\Form\Element\Input\Checkbox(
                                    'rm_field_file_' . $field->id, [$value => 'Remove?']
                                );
                                $controller->view()->form->insertElementAfter($key, $rmCheckbox);
                                $value = null;
                            }
                            if ((strpos($field->type, '-history') !== false) && (null !== $fv->history)) {
                                $history    = [0 => 'Current'];
                                $historyAry = json_decode($fv->history, true);
                                krsort($historyAry);
                                foreach ($historyAry as $time => $fieldValue) {
                                    $history[$time] = date('M j, Y H:i:s', $time);
                                }

                                $revision = new \Pop\Form\Element\Select('history_' . $modelId . '_' . $field->id, $history);
                                $revision->setLabel('Select Revision');
                                $revision->setAttribute('onchange', 'phire.changeHistory(this, \'' . BASE_PATH . APP_URI . '\');');
                                $controller->view()->form->insertElementAfter($key, $revision);
                            }
                            $controller->view()->form->{$key} = $value;
                        }
                    }
                }
            }
        }
    }

    /**
     * Save dynamic field values
     *
     * @param  \Phire\Controller\AbstractController $controller
     * @param  \Phire\Application $application
     * @return void
     */
    public static function save(\Phire\Controller\AbstractController $controller, \Phire\Application $application)
    {
        if (($_POST) && ($controller->hasView()) && (null !== $controller->view()->id) &&
            (null !== $controller->view()->form) && ($controller->view()->form instanceof \Pop\Form\Form)) {
            $fields  = $controller->view()->form->getFields();
            $modelId = $controller->view()->id;

            foreach ($_POST as $key => $value) {
                if (substr($key, 0, 14) == 'rm_field_file_') {
                    $fieldId = (int)substr($key, (strrpos($key, '_') + 1));
                    $fv      = Table\FieldValues::findById([$fieldId, $modelId]);
                    if (isset($fv->field_id)) {
                        $oldFile = json_decode($fv->value);
                        if (file_exists($_SERVER['DOCUMENT_ROOT'] . BASE_PATH . CONTENT_PATH . '/assets/fields/files/' . $oldFile)) {
                            unlink($_SERVER['DOCUMENT_ROOT'] . BASE_PATH . CONTENT_PATH . '/assets/fields/files/' . $oldFile);
                        }
                        $fv->delete();
                    }
                }
            }

            foreach ($fields as $key => $value) {
                if (substr($key, 0, 6) == 'field_') {
                    $fieldId = (int)substr($key, 6);
                    $field   = Table\Fields::findById($fieldId);
                    if (isset($field->id)) {
                        $fv = Table\FieldValues::findById([$fieldId, $modelId]);

                        if (($field->type == 'file') && isset($_FILES[$key]) &&
                            !empty($_FILES[$key]['tmp_name']) && !empty($_FILES[$key]['name'])) {
                            if (isset($fv->field_id)) {
                                $oldFile = json_decode($fv->value);
                                if (file_exists($_SERVER['DOCUMENT_ROOT'] . BASE_PATH . CONTENT_PATH . '/assets/fields/files/' . $oldFile)) {
                                    unlink($_SERVER['DOCUMENT_ROOT'] . BASE_PATH . CONTENT_PATH . '/assets/fields/files/' . $oldFile);
                                }
                            }
                            $upload = new Upload(
                                $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . CONTENT_PATH . '/assets/fields/files',
                                $application->module('Fields')['max_size'], $application->module('Fields')['allowed_types']
                            );
                            $value = $upload->upload($_FILES[$key]['tmp_name'], $_FILES[$key]['name']);
                        }

                        if (!empty($value)) {
                            if (($field->encrypt) && !is_array($value)) {
                                $value = (new Mcrypt())->create($value);
                            }
                        }

                        if (isset($fv->field_id)) {
                            if (!empty($value)) {
                                if (strpos($field->type, '-history') !== false) {
                                    $oldValue = json_decode($fv->value, true);
                                    if ($value != $oldValue) {
                                        $ts = (null !== $fv->timestamp) ? $fv->timestamp : time() - 180;
                                        if (null !== $fv->history) {
                                            $history      = json_decode($fv->history, true);
                                            $history[$ts] = $oldValue;
                                            if (count($history) > $application->module('Fields')['history']) {
                                                $history = array_slice($history, 1, $application->module('Fields')['history'], true);
                                            }
                                            $fv->history = json_encode($history);
                                        } else {
                                            $fv->history = json_encode([$ts => $oldValue]);
                                        }
                                    }
                                }
                                $fv->value = json_encode($value);
                                $fv->timestamp = time();
                                $fv->save();
                            } else {
                                $fv->delete();
                            }
                        } else {
                            if (!empty($value)) {
                                $fv = new Table\FieldValues([
                                    'field_id'  => $fieldId,
                                    'model_id'  => $modelId,
                                    'value'     => json_encode($value),
                                    'timestamp' => time()
                                ]);
                                $fv->save();
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Delete dynamic field values
     *
     * @return void
     */
    public static function delete()
    {
        if ($_POST) {
            foreach ($_POST as $key => $value) {
                if ((substr($key, 0, 3) == 'rm_') && is_array($value)) {
                    foreach ($value as $id) {
                        $fv = new Table\FieldValues();
                        $fv->delete(['model_id' => (int)$id]);
                    }
                }
            }
        }
    }

}
