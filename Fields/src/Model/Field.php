<?php

namespace Fields\Model;

use Fields\Table;
use Phire\Model\AbstractModel;
use Pop\Web\Cookie;
use Pop\File\Dir;

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
     * Get uploaded files
     *
     * @param  string $dir
     * @param  int    $limit
     * @param  int    $page
     * @return array
     */
    public function getAllFiles($dir, $limit = null, $page = null)
    {
        $files = [];
        $d     = new Dir($_SERVER['DOCUMENT_ROOT'] . $dir);
        foreach ($d->getFiles() as $file) {
            if ($file != 'index.html') {
                $files[$dir . '/' . $file] = $file;
            }
        }

        if (count($files) > $limit) {
            $offset = ((null !== $page) && ((int)$page > 1)) ?
                ($page * $limit) - $limit : 0;
            $files = array_slice($files, $offset, $limit, true);
        }

        return $files;
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
            'prepend'        => (int)$fields['prepend'],
            'dynamic'        => (int)$fields['dynamic'],
            'editor'         => (!empty($fields['editor']) && (strpos($fields['type'], 'textarea') !== false)) ?
                $fields['editor'] : null,
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
            $field->prepend        = (int)$fields['prepend'];
            $field->dynamic        = (int)$fields['dynamic'];
            $field->editor         = (!empty($fields['editor']) && (strpos($fields['type'], 'textarea') !== false)) ?
                $fields['editor'] : null;
            $field->models         = serialize($this->getModels());
            $field->save();

            $this->data = array_merge($this->data, $field->getColumns());
        }
    }

    /**
     * Remove a field
     *
     * @param  array $fields
     * @param  string $dir
     * @return void
     */
    public function remove(array $fields, $dir)
    {
        if (isset($fields['rm_fields'])) {
            foreach ($fields['rm_fields'] as $id) {
                $field = Table\Fields::findById((int)$id);
                if (isset($field->id)) {
                    if ($field->type == 'file') {
                        $values = Table\FieldValues::findBy(['field_id' => $field->id]);
                        foreach ($values->rows() as $value) {
                            $val = json_decode($value->value);
                            if (is_array($val)) {
                                foreach ($val as $v) {
                                    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $dir . '/' . $v)) {
                                        unlink($_SERVER['DOCUMENT_ROOT'] . $dir . '/' . $v);
                                    }
                                }
                            } else if (file_exists($_SERVER['DOCUMENT_ROOT'] . $dir . '/' . $val)) {
                                unlink($_SERVER['DOCUMENT_ROOT'] . $dir . '/' . $val);
                            }
                        }
                    }
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
     * Determine if list of fields has pages
     *
     * @param  string $dir
     * @param  int    $limit
     * @return boolean
     */
    public function hasFiles($dir, $limit)
    {
        $files = $this->getAllFiles($dir);
        return (count($files) > $limit);
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
     * Determine if list of fields has pages
     *
     * @param  string $dir
     * @return int
     */
    public function getFileCount($dir)
    {
        $files = $this->getAllFiles($dir);
        return count($files);
    }

    /**
     * Add user roles to models of the module config for the application
     *
     * @param  \Phire\Application $application
     * @return void
     */
    public static function addModels(\Phire\Application $application)
    {
        $path = BASE_PATH . APP_URI;
        if ($path == '') {
            $path = '/';
        }

        $cookie = Cookie::getInstance(['path' => $path]);
        if (isset($cookie->phire)) {
            $phire = (array)$cookie->phire;
            if (!isset($phire['fields_upload_folder'])) {
                $phire['fields_upload_folder'] = $application->module('Fields')['upload_folder'];
                $cookie->set('phire', $phire);
            }
        }

        $modules = $application->modules();
        $roles   = \Phire\Table\Roles::findAll();
        foreach ($roles->rows() as $role) {
            if (isset($modules['Fields']) && isset($modules['Fields']['models']) &&
                isset($modules['Fields']['models']['Phire\Model\User'])) {
                $modules['Fields']['models']['Phire\Model\User'][] = [
                    'type_field' => 'role_id',
                    'type_value' => $role->id,
                    'type_name'  => $role->name
                ];
            }
        }

        foreach ($modules as $module => $config) {
            if (($module != 'Fields') && isset($config['models'])) {
                $modules['Fields']['models'] = array_merge($config['models'], $modules['Fields']['models']);
            }
        }

        $application->mergeModuleConfig('Fields', $modules['Fields']);
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
        $fields = Table\Fields::findBy(['group_id' => null], null, ['order' => 'order']);
        $groups = Table\FieldGroups::findAll(null, ['order' => 'order']);

        if ($fields->count() > 0) {
            foreach ($fields->rows() as $field) {
                $field->validators = unserialize($field->validators);
                $field->models     = unserialize($field->models);
                foreach ($field->models as $model) {
                    $form = str_replace('Model', 'Form', $model['model']);
                    if (isset($forms[$form]) && (self::isAllowed($model, $application))) {
                        end($forms[$form]);
                        $key = key($forms[$form]);
                        reset($forms[$form]);

                        $fieldConfig = self::createFieldConfig($field);

                        if ($field->dynamic) {
                            if (isset($fieldConfig['label'])) {
                                $fieldConfig['label'] = '<a href="#" onclick="return phire.addField(' .
                                    $field->id . ');">[+]</a> ' . $fieldConfig['label'];
                            } else {
                                $fieldConfig['label'] = '<a href="#" onclick="return phire.addField(' .
                                    $field->id . ');">[+]</a>';
                            }
                            if (isset($fieldConfig['attributes'])) {
                                $fieldConfig['attributes']['data-path'] = BASE_PATH . APP_URI;
                            } else {
                                $fieldConfig['attributes'] = [
                                    'data-path' => BASE_PATH . APP_URI
                                ];
                            }
                        }

                        if (is_numeric($key)) {
                            if ($field->prepend) {
                                $forms[$form][$key] = array_merge(
                                    ['field_' . $field->id => $fieldConfig], $forms[$form][$key]
                                );
                            } else {
                                $forms[$form][$key]['field_' . $field->id] = $fieldConfig;
                            }
                        } else {
                            if ($field->prepend) {
                                $forms[$form] = array_merge(['field_' . $field->id => $fieldConfig], $forms[$form]);
                            } else {
                                $forms[$form]['field_' . $field->id] = $fieldConfig;
                            }
                        }
                    }
                }
            }
        }

        $fieldGroups  = [];
        $groupPrepend = [];

        if ($groups->count() > 0) {
            $tab = 1001;
            foreach ($groups->rows() as $group) {
                $groupPrepend[$group->id] = (bool)$group->prepend;

                $fields = Table\Fields::findBy(['group_id' => $group->id], null, ['order' => 'order']);

                if ($fields->count() > 0) {
                    $i = 0;
                    foreach ($fields->rows() as $field) {
                        $field->validators = unserialize($field->validators);
                        $field->models     = unserialize($field->models);
                        foreach ($field->models as $model) {
                            $form = str_replace('Model', 'Form', $model['model']);
                            if (isset($forms[$form]) && (self::isAllowed($model, $application))) {
                                $fieldConfig = self::createFieldConfig($field);
                                if ($field->dynamic) {
                                    if (isset($fieldConfig['label'])) {
                                        $fieldConfig['label'] = '<a href="#" onclick="return phire.addField(' .
                                            $field->id . ');">[+]</a> ' . $fieldConfig['label'];
                                    } else {
                                        $fieldConfig['label'] = '<a href="#" onclick="return phire.addField(' .
                                            $field->id . ']);">[+]</a>';
                                    }
                                }

                                if (isset($fieldConfig['attributes'])) {
                                    $fieldConfig['attributes']['tabindex'] = $tab;
                                    $fieldConfig['attributes']['data-path'] = BASE_PATH . APP_URI;
                                } else {
                                    $fieldConfig['attributes'] = [
                                        'tabindex'  => $tab,
                                        'data-path' => BASE_PATH . APP_URI
                                    ];
                                }
                                $tab++;

                                if (!isset($fieldGroups[$form])) {
                                    $fieldGroups[$form] = [];
                                }
                                if (!isset($fieldGroups[$form][$field->group_id])) {
                                    $fieldGroups[$form][$field->group_id] = [];
                                }

                                if ($field->prepend) {
                                    $fieldGroups[$form][$field->group_id] = array_merge(
                                        ['field_' . $field->id => $fieldConfig], $fieldGroups[$form][$field->group_id]
                                    );
                                } else {
                                    $fieldGroups[$form][$field->group_id]['field_' . $field->id] = $fieldConfig;
                                }
                            }
                        }
                        $i++;
                    }
                }
            }
        }

        foreach ($fieldGroups as $form => $configs) {
            $keys    = array_keys($forms[$form]);
            $numeric = true;
            foreach ($keys as $key) {
                if (!is_numeric($key)) {
                    $numeric = false;
                }
            }

            $formConfig = ($numeric) ? $forms[$form] : [$forms[$form]];

            foreach ($configs as $id => $config) {
                if ($groupPrepend[$id]) {
                    $formConfig = array_merge($config, $formConfig);
                } else {
                    $formConfig[] = $config;
                }
            }

            $forms[$form] = $formConfig;
        }

        $application->mergeConfig(['forms' => $forms], true);
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
            if ((strpos($key, 'validator_') !== false) && (strpos($key, 'validator_value_') === false) &&
                (strpos($key, 'validator_message_') === false) && ($value != '----')) {
                $id         = substr($key, 10);
                $valValue   = (!empty($_POST['validator_value_' . $id]))   ? $_POST['validator_value_' . $id]   : null;
                $valMessage = (!empty($_POST['validator_message_' . $id])) ? $_POST['validator_message_' . $id] : null;
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
            if ((strpos($key, 'model_') !== false) && (strpos($key, 'model_type_') === false) && ($value != '----')) {
                $id        = substr($key, 6);
                $typeField = null;
                $typeValue = null;

                if ($_POST['model_type_' . $id] != '----') {
                    $type = explode('|', $_POST['model_type_' . $id]);
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


    /**
     * Determine if the field is allowed for the form
     *
     * @param  array $model
     * @param  \Phire\Application $application
     * @return boolean
     */
    protected static function isAllowed(array $model, \Phire\Application $application)
    {
        $allowed = true;

        // Determine if there is a model type restraint on the field
        if (!empty($model['type_field']) && !empty($model['type_value']) &&
            (count($application->router()->getRouteMatch()->getDispatchParams()) > 0)) {
            $params = $application->router()->getRouteMatch()->getDispatchParams();
            reset($params);
            $id = $params[key($params)];
            if (substr($application->router()->getRouteMatch()->getRoute(), -4) == 'edit') {
                $modelClass  = $model['model'];
                $modelType   = $model['type_field'];
                $modelObject = new $modelClass();
                if (method_exists($modelObject, 'getById')) {
                    $modelObject->getById($id);
                    $allowed = (isset($modelObject->{$modelType}) &&
                        ($modelObject->{$modelType} == $model['type_value']));
                }
            } else if (substr($application->router()->getRouteMatch()->getRoute(), -3) == 'add') {
                $allowed = ($model['type_value'] == $id);
            }
        }

        return $allowed;
    }

    /**
     * Create field config from field object
     *
     * @param  \ArrayObject $field
     * @return array
     */
    protected static function createFieldConfig(\ArrayObject $field)
    {
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
                    if ((strpos($fvAry[0], '\Table\\') !== false) && (count($fvAry) == 3)) {
                        $class    = $fvAry[0];
                        $optValue = $fvAry[1];
                        $optName  = $fvAry[2];
                        $vals     = $class::findAll();
                        if ($vals->count() > 0) {
                            foreach ($vals->rows() as $v) {
                                if (isset($v->{$optValue}) && isset($v->{$optName})) {
                                    $fieldValues[$v->{$optValue}] = $v->{$optName};
                                }
                            }
                        }
                    } else {
                        $fieldValues[$fvAry[0]] = $fvAry[1];
                    }
                } else {
                    $fieldValues[$fv] = $fv;
                }
            }
        } else if (strpos($field->values, '::')) {
            $fvAry = explode('::', $field->values);
            if ((strpos($fvAry[0], '\Table\\') !== false) && (count($fvAry) == 3)) {
                $fieldValues = [];
                $class    = $fvAry[0];
                $optValue = $fvAry[1];
                $optName  = $fvAry[2];
                $vals     = $class::findAll();
                if ($vals->count() > 0) {
                    foreach ($vals->rows() as $v) {
                        if (isset($v->{$optValue}) && isset($v->{$optName})) {
                            $fieldValues[$v->{$optValue}] = $v->{$optName};
                        }
                    }
                }
            } else {
                $fieldValues = [$fvAry[0] => $fvAry[1]];
            }
        } else {
            $fieldValues = $field->values;
        }

        $label = ((null !== $field->editor) && ($field->editor != 'source')) ?
            $label = $field->label . ' <span class="editor-link-span">[ <a class="editor-link" data-editor="' .
                $field->editor . '" data-fid="' . $field->id . '" data-path="' . BASE_PATH . CONTENT_PATH .
                '" href="#">Source</a> ]</span>' :
            $field->label;

        return [
            'type'       => ((strpos($field->type, '-history') !== false) ?
                substr($field->type, 0, strpos($field->type, '-history')) : $field->type),
            'label'      => $label,
            'required'   => (bool)$field->required,
            'attributes' => $attribs,
            'validators' => $validators,
            'value'      => $fieldValues,
            'marked'     => (strpos($field->default_values, '|')) ?
                explode('|', $field->default_values) : $field->default_values,
        ];
    }

}
