<?php

namespace Phire\Fields\Model;

use Phire\Fields\Table;
use Phire\Model\AbstractModel;
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
        $d     = new Dir($_SERVER['DOCUMENT_ROOT'] . $dir, false, false, false);

        foreach ($d->getFiles() as $file) {
            if (($file != 'index.html')) {
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
     * @param  array $config
     * @return void
     */
    public function remove(array $fields, array $config)
    {
        if (isset($fields['rm_fields'])) {
            $uploadFolder = $config['upload_folder'];

            foreach ($fields['rm_fields'] as $id) {
                $field = Table\Fields::findById((int)$id);
                if (isset($field->id)) {
                    if ($field->type == 'file') {
                        $values = Table\FieldValues::findBy(['field_id' => $field->id]);
                        foreach ($values->rows() as $value) {
                            $val = json_decode($value->value);
                            if (is_array($val)) {
                                foreach ($val as $v) {
                                    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $v)) {
                                        unlink($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $v);
                                    }
                                }
                            } else if (file_exists($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $val)) {
                                unlink($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $val);
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

}
