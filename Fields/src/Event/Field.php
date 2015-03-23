<?php

namespace Fields\Event;

use Fields\Table;
use Pop\Application;
use Pop\Web\Cookie;

class Field
{

    /**
     * Bootstrap the module
     *
     * @param  Application $application
     * @return void
     */
    public static function bootstrap(Application $application)
    {
        $path = BASE_PATH . APP_URI;
        if ($path == '') {
            $path = '/';
        }

        $cookie = Cookie::getInstance(['path' => $path]);
        if (isset($cookie->phire)) {
            $phire = (array)$cookie->phire;
            if (!isset($phire['fields_upload_folder'])) {
                $phire['fields_upload_folder'] = $application->module('Fields')->config()['upload_folder'];
            }
            if (!isset($phire['fields_media_library'])) {
                $phire['fields_media_library'] = $application->module('Fields')->config()['media_library'];
            }
            $cookie->set('phire', $phire);
        }

        $modules = $application->modules();
        $roles   = \Phire\Table\Roles::findAll();
        foreach ($roles->rows() as $role) {
            if (isset($modules['Fields']) && isset($modules['Fields']->config()['models']) &&
                isset($modules['Fields']->config()['models']['Phire\Model\User'])) {
                $models = $modules['Fields']->config()['models'];
                $models['Phire\Model\User'][] = [
                    'type_field' => 'role_id',
                    'type_value' => $role->id,
                    'type_name'  => $role->name
                ];
                $application->module('Fields')->mergeConfig(['models' => $models]);
            }
        }

        foreach ($modules as $module => $config) {
            if (($module != 'Fields') && isset($config['models'])) {
                $application->module('Fields')->mergeConfig(['models' => $config['models']]);
            }
        }
    }

    /**
     * Add dynamic fields to the form configs
     *
     * @param  Application $application
     * @return void
     */
    public static function addFields(Application $application)
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
     * Determine if the field is allowed for the form
     *
     * @param  array       $model
     * @param  Application $application
     * @return boolean
     */
    protected static function isAllowed(array $model, Application $application)
    {
        $allowed = true;

        // Determine if there is a model type restraint on the field
        if (!empty($model['type_field']) && !empty($model['type_value']) &&
            (count($application->router()->getRouteMatch()->getDispatchParams()) > 0)) {
            $params = $application->router()->getRouteMatch()->getDispatchParams();
            if (isset($params['id'])) {
                $id = $params['id'];
                if (substr($application->router()->getRouteMatch()->getRoute(), -4) == 'edit') {
                    $modelClass  = $model['model'];
                    $modelType   = $model['type_field'];
                    $modelObject = new $modelClass();
                    if (method_exists($modelObject, 'getById')) {
                        $modelObject->getById($id);
                        $allowed = (isset($modelObject->{$modelType}) &&
                            ($modelObject->{$modelType} == $model['type_value']));
                    }
                }
            } else {
                $type_id = $params[key($params)];
                $allowed = ($model['type_value'] == $type_id);
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