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

            return Table\Fields::findAll([
                'offset' => $page,
                'limit'  => $limit,
                'order'  => $order
            ])->rows();
        } else {
            return Table\Fields::findAll([
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
     * @return array
     */
    public function getAllFiles($dir)
    {
        $files = [];
        $d     = new Dir($_SERVER['DOCUMENT_ROOT'] . $dir, ['filesOnly' => true]);

        foreach ($d->getFiles() as $file) {
            if ($file != 'index.html') {
                $files[$dir . '/' . $file] = $file;
            }
        }

        return $files;
    }

    /**
     * Get uploaded images
     *
     * @param  string $dir
     * @return array
     */
    public function getAllImages($dir)
    {
        $images = [];
        $d      = new Dir($_SERVER['DOCUMENT_ROOT'] . $dir, ['filesOnly' => true]);

        foreach ($d->getFiles() as $file) {
            if (($file != 'index.html') && (preg_match('/^.*\.(jpg|jpeg|png|gif)$/i', $file) == 1)) {
                $images[$dir . '/' . $file] = $file;
            }
        }

        return $images;
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
            'storage'        => $fields['storage'],
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

        if ($field->storage != 'eav') {
            $this->createFieldTable($field->name, $field->storage);
        }

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
            $oldStorage   = $field->storage;
            $oldFieldName = $field->name;

            $field->group_id       = ($fields['group_id'] != '----') ? (int)$fields['group_id'] : null;
            $field->storage        = $fields['storage'];
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

            if (($oldStorage != 'eav') && ($field->storage == 'eav')) {
                $this->dropFieldTable($field->name);
            } else if (($oldStorage == 'eav') && ($field->storage != 'eav')) {
                $f = new Table\FieldValues();
                $f->delete(['field_id' => $field->id]);
                $this->createFieldTable($field->name, $field->storage);
            } else if (($oldStorage != 'eav') && ($field->storage != 'eav')) {
                if (($oldStorage != $field->storage) || ($oldFieldName != $field->name)) {
                    $this->updateFieldTable($field->name, $oldFieldName, $field->storage);
                }
            }

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
            $uploadFolder = BASE_PATH . CONTENT_PATH . '/files';
            $mediaLibrary = $config['media_library'];

            foreach ($fields['rm_fields'] as $id) {
                $field = Table\Fields::findById((int)$id);
                if (isset($field->id)) {
                    if ($field->type == 'file') {
                        if ($field->storage == 'eav') {
                            $values = Table\FieldValues::findBy(['field_id' => $field->id]);
                            foreach ($values->rows() as $value) {
                                $val = json_decode($value->value);
                                if (is_array($val)) {
                                    foreach ($val as $v) {
                                        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $v)) {
                                            unlink($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $v);
                                            if ((null !== $mediaLibrary) && class_exists('Phire\Media\Model\Media')) {
                                                $media = new \Phire\Media\Model\Media();
                                                $media->getByFile($v);

                                                if (isset($media->id) && ($media->library_folder == $mediaLibrary)) {
                                                    $media->remove(['rm_media' => [$media->id]]);
                                                }
                                            }
                                        }
                                    }
                                } else if (file_exists($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $val)) {
                                    unlink($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $val);
                                    if ((null !== $mediaLibrary) && class_exists('Phire\Media\Model\Media')) {
                                        $media = new \Phire\Media\Model\Media();
                                        $media->getByFile($val);

                                        if (isset($media->id) && ($media->library_folder == $mediaLibrary)) {
                                            $media->remove(['rm_media' => [$media->id]]);
                                        }
                                    }
                                }
                            }
                        } else {
                            $fv = new Record();
                            $fv->setPrefix(DB_PREFIX)
                                ->setPrimaryKeys(['id'])
                                ->setTable('field_' . $field->name);

                            $fv->findAllRecords();
                            if ($fv->hasRows()) {
                                foreach ($fv->rows() as $f) {
                                    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $f->value)) {
                                        unlink($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' .  $f->value);
                                        if ((null !== $mediaLibrary) && class_exists('Phire\Media\Model\Media')) {
                                            $media = new \Phire\Media\Model\Media();
                                            $media->getByFile($f->value);

                                            if (isset($media->id) && ($media->library_folder == $mediaLibrary)) {
                                                $media->remove(['rm_media' => [$media->id]]);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if ($field->storage != 'eav') {
                        $this->dropFieldTable($field->name);
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
     * Get count of fields
     *
     * @return int
     */
    public function getCount()
    {
        return Table\Fields::findAll()->count();
    }

    /**
     * Get file count
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
     * Get image count
     *
     * @param  string $dir
     * @return int
     */
    public function getImageCount($dir)
    {
        $images = $this->getAllImages($dir);
        return count($images);
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
     * Create field table
     *
     * @param  string $name
     * @param  string $type
     * @return void
     */
    protected function createFieldTable($name, $type)
    {
        $sql   = Table\Fields::sql();
        $db    = $sql->getDb();
        $table = $sql->quoteId(DB_PREFIX . 'field_' . $name);

        $fieldType = null;

        if ($sql->getDbType() == \Pop\Db\Sql::MYSQL) {
            $idSql     = $sql->quoteId('id') . ' integer NOT NULL AUTO_INCREMENT, ';
            $keySql    = 'PRIMARY KEY (' . $sql->quoteId('id') . ')';
            if ($type == 'text') {
                $fieldType = 'mediumtext';
            } else {
                $fieldType = $type . (($type == 'varchar') ? '(255)' : '');
            }
        } else if ($sql->getDbType() == \Pop\Db\Sql::PGSQL) {
            $idSql     = $sql->quoteId('id') . ' integer NOT NULL DEFAULT nextval(\'field_' . $name . '_id_seq\'), ';
            $keySql    = 'PRIMARY KEY (' . $sql->quoteId('id') . ')';
            if ($type == 'float') {
                $fieldType = 'real';
            } else {
                $fieldType = $type . (($type == 'varchar') ? '(255)' : '');
            }
        } else if ($sql->getDbType() == \Pop\Db\Sql::SQLITE) {
            $idSql  = $sql->quoteId('id') . ' integer NOT NULL PRIMARY KEY AUTOINCREMENT, ';
            $keySql = 'UNIQUE (' . $sql->quoteId('id') . ')';
            if ($type == 'varchar') {
                $fieldType = 'text';
            } else if ($type == 'float') {
                $fieldType = 'real';
            } else {
                $fieldType = $type;
            }
        }

        if (null !== $fieldType) {
            // Create PGSQL sequence
            if ($sql->getDbType() == \Pop\Db\Sql::PGSQL) {
                $db->query('CREATE SEQUENCE field_' . $name . '_id_seq START 1');
            }

            // Create table
            $query = 'CREATE TABLE ' . $table . ' (' .
                $idSql .
                $sql->quoteId('model_id') . ' integer NOT NULL, ' .
                $sql->quoteId('model') . ' varchar' . (($sql->getDbType() != \Pop\Db\Sql::SQLITE) ? '(255)' : '') . ' NOT NULL, ' .
                $sql->quoteId('timestamp') . ' integer, ' .
                $sql->quoteId('revision') . ' integer, ' .
                $sql->quoteId('value') . ' ' . $fieldType . ', ' .
                $keySql . ')';

            $db->query($query);

            // Add sequences
            if ($sql->getDbType() == \Pop\Db\Sql::PGSQL) {
                $db->query('ALTER SEQUENCE field_' . $name . '_id_seq OWNED BY ' . $table .'."id";');
            } else if ($sql->getDbType() == \Pop\Db\Sql::SQLITE) {
                $db->query('INSERT INTO "sqlite_sequence" ("name", "seq") VALUES (\'' . DB_PREFIX . 'field_' . $name . '\', 0);');
            }

            // Add indices
            $db->query('CREATE INDEX ' . $sql->quoteId('model_id_' . $name) . ' ON ' . $table . ' (' . $sql->quoteId('model_id') . ')');
            $db->query('CREATE INDEX ' . $sql->quoteId('model_' . $name) . ' ON ' . $table . ' (' . $sql->quoteId('model') . ')');

            $module = \Phire\Table\Modules::findBy(['folder' => 'phire-fields']);
            if (isset($module->id)) {
                $assets = unserialize($module->assets);
                $assets['tables'][] = DB_PREFIX . 'field_' . $name;
                $module->assets = serialize($assets);
                $module->save();
            }
        }
    }

    /**
     * Update field table
     *
     * @param  int    $name
     * @param  string $oldFieldName
     * @param  string $type
     * @return void
     */
    protected function updateFieldTable($name, $oldFieldName, $type)
    {
        $sql      = Table\Fields::sql();
        $db       = $sql->getDb();
        $oldTable = $sql->quoteId(DB_PREFIX . 'field_' . $oldFieldName);
        $newTable = $sql->quoteId(DB_PREFIX . 'field_' . $name);

        if ($sql->getDbType() == \Pop\Db\Sql::MYSQL) {
            $fieldType = $type . (($type == 'varchar') ? '(255)' : '');
        } else if ($sql->getDbType() == \Pop\Db\Sql::PGSQL) {
            if ($type == 'float') {
                $fieldType = 'real';
            } else {
                $fieldType = $type . (($type == 'varchar') ? '(255)' : '');
            }
        } else if ($sql->getDbType() == \Pop\Db\Sql::SQLITE) {
            if ($type == 'varchar') {
                $fieldType = 'text';
            } else if ($type == 'float') {
                $fieldType = 'real';
            } else {
                $fieldType = $type;
            }
        }

        $db->query('ALTER TABLE ' . $oldTable . ' MODIFY COLUMN ' . $sql->quoteId('value') . ' ' . $fieldType);

        if ($name != $oldFieldName) {
            $db->query('ALTER TABLE ' . $oldTable . ' RENAME TO ' . $newTable);
        }
    }


    /**
     * Drop field table
     *
     * @param  string $name
     * @return void
     */
    protected function dropFieldTable($name)
    {
        $sql   = Table\Fields::sql();
        $db    = $sql->getDb();
        $table = $sql->quoteId(DB_PREFIX . 'field_' . $name);

        $db->query('DROP TABLE ' . $table);

        $module = \Phire\Table\Modules::findBy(['folder' => 'phire-fields']);
        if (isset($module->id)) {
            $assets = unserialize($module->assets);
            if (in_array(DB_PREFIX . 'field_' . $name, $assets['tables'])) {
                unset($assets['tables'][array_search(DB_PREFIX . 'field_' . $name, $assets['tables'])]);
            }
            $module->assets = serialize($assets);
            $module->save();
        }
    }

}
