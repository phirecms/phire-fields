<?php

namespace Fields\Model;

use Fields\Table;
use Pop\Crypt\Mcrypt;
use Pop\File\Upload;
use Phire\Model\AbstractModel;

class Field extends AbstractModel
{

    /**
     * Get all fields
     *
     * @param  int    $limit
     * @param  int    $page
     * @param  string $sort
     * @return array
     */
    public function getAll($limit = null, $page = null, $sort = null)
    {
        $order = $this->getSortOrder($sort, $page);

        if (null !== $limit) {
            $page = ((null !== $page) && ((int)$page > 1)) ?
                ($page * $limit) - $limit : null;

            return Table\Fields::findAll(null, [
                'offset' => $page,
                'limit'  => $limit,
                'order'  => $order
            ])->rows();
        } else {
            return Table\Fields::findAll(null, [
                'order'  => $order
            ])->rows();
        }
    }

    /**
     * Get field by ID
     *
     * @param  int $id
     * @return void
     */
    public function getById($id)
    {
        $field = Table\Fields::findById($id);
        if (isset($field->id)) {
            $field->validators = unserialize($field->validators);
            $field->models     = unserialize($field->models);
            $this->data        = array_merge($this->data, $field->getColumns());
        }
    }

    /**
     * Save new field
     *
     * @param  array $fields
     * @return void
     */
    public function save(array $fields)
    {
        $field = new Table\Fields([
            'group_id'       => ($fields['group_id'] != '----') ? (int)$fields['group_id'] : null,
            'type'           => $fields['type'],
            'name'           => $fields['name'],
            'label'          => (!empty($fields['label'])) ? $fields['label'] : null,
            'values'         => (!empty($fields['values'])) ? $fields['values'] : null,
            'default_values' => (!empty($fields['default_values'])) ? $fields['default_values'] : null,
            'attributes'     => (!empty($fields['attributes'])) ? $fields['attributes'] : null,
            'validators'     => serialize($this->getValidators()),
            'encrypt'        => (!empty($fields['encrypt'])) ? (int)$fields['encrypt'] : 0,
            'order'          => (!empty($fields['order'])) ? (int)$fields['order'] : 0,
            'required'       => (!empty($fields['required'])) ? (int)$fields['required'] : 0,
            'editor'         => (!empty($fields['editor'])) ? $fields['editor'] : null,
            'models'         => serialize($this->getModels())
        ]);
        $field->save();

        $this->data = array_merge($this->data, $field->getColumns());
    }

    /**
     * Update an existing field
     *
     * @param  array $fields
     * @return void
     */
    public function update(array $fields)
    {
        $field = Table\Fields::findById($fields['id']);
        if (isset($field->id)) {
            $field->group_id       = ($fields['group_id'] != '----') ? (int)$fields['group_id'] : null;
            $field->type           = $fields['type'];
            $field->name           = $fields['name'];
            $field->label          = (!empty($fields['label'])) ? $fields['label'] : null;
            $field->values         = (!empty($fields['values'])) ? $fields['values'] : null;
            $field->default_values = (!empty($fields['default_values'])) ? $fields['default_values'] : null;
            $field->attributes     = (!empty($fields['attributes'])) ? $fields['attributes'] : null;
            $field->validators     = serialize($this->getValidators());
            $field->encrypt        = (!empty($fields['encrypt'])) ? (int)$fields['encrypt'] : 0;
            $field->order          = (!empty($fields['order'])) ? (int)$fields['order'] : 0;
            $field->required       = (!empty($fields['required'])) ? (int)$fields['required'] : 0;
            $field->editor         = (!empty($fields['editor'])) ? $fields['editor'] : null;
            $field->models         = serialize($this->getModels());
            $field->save();

            $this->data = array_merge($this->data, $field->getColumns());
        }
    }

    /**
     * Remove a field
     *
     * @param  array $fields
     * @return void
     */
    public function remove(array $fields)
    {
        if (isset($fields['rm_fields'])) {
            foreach ($fields['rm_fields'] as $id) {
                $field = Table\Fields::findById((int)$id);
                if (isset($field->id)) {
                    $field->delete();
                }
            }
        }
    }

    /**
     * Determine if list of fields has pages
     *
     * @param  int $limit
     * @return boolean
     */
    public function hasPages($limit)
    {
        return (Table\Fields::findAll()->count() > $limit);
    }

    /**
     * Get count of fields
     *
     * @return int
     */
    public function getCount()
    {
        return Table\Fields::findAll()->count();
    }

    /**
     * Get models config for the application
     *
     * @param  \Phire\Application $application
     * @return void
     */
    public static function addModels(\Phire\Application $application)
    {
        $config = $application->module('Fields');
        $roles  = \Phire\Table\UserRoles::findAll();
        foreach ($roles->rows() as $role) {
            if (isset($config['models']) && isset($config['models']['Phire\Model\User'])) {
                $config['models']['Phire\Model\User'][] = [
                    'type_field' => 'role_id',
                    'type_value' => $role->id,
                    'type_name'  => $role->name
                ];
            }
        }
        $application->mergeModuleConfig('Fields', $config);
    }

    /**
     * Add dynamic fields to the form configs
     *
     * @param  \Phire\Application $application
     * @return void
     */
    public static function addFields(\Phire\Application $application)
    {
        $forms  = $application->config()['forms'];
        $fields = Table\Fields::findAll(null, ['order' => 'order']);

        if ($fields->count() > 0) {
            foreach ($fields->rows() as $field) {
                $field->validators = unserialize($field->validators);
                $field->models     = unserialize($field->models);
                foreach ($field->models as $model) {
                    $form = str_replace('Model', 'Form', $model['model']);
                    if (isset($forms[$form])) {
                        end($forms[$form]);
                        $key = key($forms[$form]);
                        reset($forms[$form]);

                        $attribs = null;
                        if (!empty($field->attributes)) {
                            $attribs    = [];
                            $attributes = explode('" ', $field->attributes);
                            foreach ($attributes as $attribute) {
                                $attributeAry = explode('=', trim($attribute));
                                $att = substr($attributeAry[1], 1);
                                if (substr($att, -1) == '"') {
                                    $att = substr($att, 0, -1);
                                }
                                $attribs[$attributeAry[0]] = $att;
                            }
                        }

                        $validators = [];
                        if (is_array($field->validators) && (count($field->validators) > 0)) {
                            foreach ($field->validators as $validator) {
                                $class   = 'Pop\Validator\\' . $validator['validator'];
                                $value   = (!empty($validator['value']))   ? $validator['value']   : null;
                                $message = (!empty($validator['message'])) ? $validator['message'] : null;
                                $validators[] = new $class($value, $message);
                            }
                        }


                        if (strpos($field->values, '|')) {
                            $fValues     = explode('|', $field->values);
                            $fieldValues = [];
                            foreach ($fValues as $fv) {
                                if (strpos($fv, '::')) {
                                    $fvAry = explode('::', $fv);
                                    $fieldValues[$fvAry[0]] = $fvAry[1];
                                } else {
                                    $fieldValues[$fv] = $fv;
                                }
                            }
                        } else if (strpos($field->values, '::')) {
                            $fvAry = explode('::', $field->values);
                            $fieldValues = [$fvAry[0] => $fvAry[1]];
                        } else {
                            $fieldValues = $field->values;
                        }

                        $fieldConfig = [
                            'type'       => $field->type,
                            'label'      => $field->label,
                            'required'   => (bool)$field->required,
                            'attributes' => $attribs,
                            'validators' => $validators,
                            'value'      => $fieldValues,
                            'marked'     => (strpos($field->default_values, '|')) ?
                                explode('|', $field->default_values) : $field->default_values,
                        ];

                        if (is_numeric($key)) {
                            $forms[$form][$key]['field_' . $field->id] = $fieldConfig;
                        } else {
                            $forms[$form]['field_' . $field->id] = $fieldConfig;
                        }
                    }
                }
            }
        }

        $application->mergeConfig(['forms' => $forms]);
    }

    /**
     * Get model object with dynamic field values
     *
     * @param  string $class
     * @param  int    $id
     * @throws \Exception
     * @return mixed
     */
    public static function getModelObject($class, $id)
    {
        $model = new $class();

        if (!method_exists($model, 'getById')) {
            throw new \Exception(
                'Error: The model class must be an instance of Phire\Model\AbstractModel and have the getById method.'
            );
        }

        $model->getById($id);

        if (isset($model->id)) {
            $model = self::getValues($model);
        }

        return $model;
    }

    /**
     * Get field values for a model object
     *
     * @param  mixed $model
     * @return mixed
     */
    public static function getValues($model)
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
     * Get dynamic field values
     *
     * @param  \Phire\Controller\AbstractController $controller
     * @param  \Phire\Application $application
     * @return void
     */
    public static function getFieldValues(\Phire\Controller\AbstractController $controller, \Phire\Application $application)
    {
        if ((!$_POST) && ($controller->hasView()) && (null !== $controller->view()->form) && ((int)$controller->view()->form->id != 0) &&
            (null !== $controller->view()->form) && ($controller->view()->form instanceof \Pop\Form\Form)) {
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
                                    $value . '" target="_blank">' . $value .
                                    '</a> ] <input type="checkbox" class="rm-field-file" name="rm_field_file_' .
                                    $field->id . '" value="' . $value . '" /><br /><br />';
                                $controller->view()->form->getElement($key)->setLabel($label);
                                $value = null;
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
    public static function saveFieldValues(\Phire\Controller\AbstractController $controller, \Phire\Application $application)
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
                            $value = basename($upload->upload($_FILES[$key]['tmp_name'], $_FILES[$key]['name']));
                        }

                        if (!empty($value)) {
                            if (($field->encrypt) && !is_array($value)) {
                                $value = (new Mcrypt())->create($value);
                            }
                        }

                        if (isset($fv->field_id)) {
                            if (!empty($value)) {
                                $fv->value = json_encode($value);
                                $fv->timestamp = time();
                                $fv->save();
                            } else {
                                $fv->delete();
                            }
                        } else {
                            if (!empty($value)) {
                                $fv = new Table\FieldValues([
                                    'field_id' => $fieldId,
                                    'model_id' => $modelId,
                                    'value' => json_encode($value),
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
     * @param  \Phire\Controller\AbstractController $controller
     * @param  \Phire\Application $application
     * @return void
     */
    public static function deleteFieldValues(\Phire\Controller\AbstractController $controller, \Phire\Application $application)
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

    /**
     * Get validators
     *
     * @return array
     */
    protected function getValidators()
    {
        $validators = [];

        // Get new ones
        foreach ($_POST as $key => $value) {
            if ((strpos($key, 'validator_new_') !== false) && ($value != '----')) {
                $id         = substr($key, 14);
                $valValue   = (!empty($_POST['validator_value_new_' . $id]))   ? $_POST['validator_value_new_' . $id]   : null;
                $valMessage = (!empty($_POST['validator_message_new_' . $id])) ? $_POST['validator_message_new_' . $id] : null;
                $validators[] = [
                    'validator' => $value,
                    'value'     => $valValue,
                    'message'   => $valMessage
                ];
            }
        }

        // Remove old ones
        foreach ($_POST as $key => $value) {
            if ((strpos($key, 'rm_validators') !== false) && isset($value[0])) {
                $id = $value[0];
                unset($_POST['validator_cur_' . $id]);
                unset($_POST['validator_value_cur_' . $id]);
                unset($_POST['validator_message_cur_' . $id]);
            }
        }

        // Save any remaining old ones
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'validator_cur_') !== false) {
                $id         = substr($key, 14);
                $valValue   = (!empty($_POST['validator_value_cur_' . $id]))   ? $_POST['validator_value_cur_' . $id]   : null;
                $valMessage = (!empty($_POST['validator_message_cur_' . $id])) ? $_POST['validator_message_cur_' . $id] : null;
                $validators[] = [
                    'validator' => $value,
                    'value'     => $valValue,
                    'message'   => $valMessage
                ];
            }
        }

        return $validators;
    }

    /**
     * Get models
     *
     * @return array
     */
    protected function getModels()
    {
        $models = [];

        // Get new ones
        foreach ($_POST as $key => $value) {
            if ((strpos($key, 'model_new_') !== false) && ($value != '----')) {
                $id        = substr($key, 10);
                $typeField = null;
                $typeValue = null;

                if ($_POST['model_type_new_' . $id] != '----') {
                    $type = explode('|', $_POST['model_type_new_' . $id]);
                    $typeField = $type[0];
                    $typeValue = $type[1];
                }

                $models[] = [
                    'model'      => $value,
                    'type_field' => $typeField,
                    'type_value' => $typeValue
                ];
            }
        }

        // Remove old ones
        foreach ($_POST as $key => $value) {
            if ((strpos($key, 'rm_models') !== false) && isset($value[0])) {
                $id = $value[0];
                unset($_POST['model_cur_' . $id]);
                unset($_POST['model_type_cur_' . $id]);
            }
        }

        // Save any remaining old ones
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'model_cur_') !== false) {
                $id        = substr($key, 10);
                $typeField = null;
                $typeValue = null;

                if ($_POST['model_type_cur_' . $id] != '----') {
                    $type = explode('|', $_POST['model_type_cur_' . $id]);
                    $typeField = $type[0];
                    $typeValue = $type[1];
                }

                $models[] = [
                    'model'      => $value,
                    'type_field' => $typeField,
                    'type_value' => $typeValue
                ];
            }
        }

        return $models;
    }

}
