<?php

namespace Phire\Fields\Event;

use Phire\Fields\Table;
use Pop\Application;
use Phire\Controller\AbstractController;
use Pop\Crypt\Mcrypt;
use Pop\Db\Record;
use Pop\File\Upload;

class FieldValue
{

    /**
     * Get all dynamic field values for the form object
     *
     * @param  AbstractController $controller
     * @param  Application        $application
     * @return void
     */
    public static function getAll(AbstractController $controller, Application $application)
    {
        if ((!$_POST) && ($controller->hasView()) && (null !== $controller->view()->form) &&
            ((int)$controller->view()->form->id != 0) && (null !== $controller->view()->form) &&
            ($controller->view()->form instanceof \Pop\Form\Form)) {
            $fields  = $controller->view()->form->getFields();
            $modelId = $controller->view()->form->id;
            $model   = str_replace('Form', 'Model', get_class($controller->view()->form));

            foreach ($fields as $key => $value) {
                if (substr($key, 0, 6) == 'field_') {
                    $fieldId = (int)substr($key, 6);
                    $field   = Table\Fields::findById($fieldId);
                    if (isset($field->id)) {
                        if ($field->storage == 'eav') {
                            $fv = Table\FieldValues::findById([$fieldId, $modelId, $model]);
                            if (isset($fv->field_id)) {
                                $fieldValue = $fv->getColumns();
                                $value = json_decode($fieldValue['value']);
                                if (($field->dynamic) && is_array($value)) {
                                    if (!isset($values[$fieldId])) {
                                        $values[$fieldId] = $value;
                                    }
                                    if (isset($value[0])) {
                                        $value = $value[0];
                                    }
                                }
                                if ($field->encrypt) {
                                    $value = (new Mcrypt())->decrypt($value);
                                }
                                if ($field->type == 'file') {
                                    $rmCheckbox = new \Pop\Form\Element\CheckboxSet(
                                        'rm_field_file_' . $field->id, [$value => 'Remove <a href="' .
                                            $application->module('phire-fields')->config()['upload_folder'] . '/' .
                                            $value . '" target="_blank">' . $value . '</a>?']
                                    );
                                    $controller->view()->form->insertElementAfter($key, $rmCheckbox);
                                    $value = null;
                                }
                                if ((strpos($field->type, '-history') !== false) && (null !== $fv->history)) {
                                    $history = [0 => 'Current'];
                                    $historyAry = json_decode($fv->history, true);
                                    krsort($historyAry);
                                    foreach ($historyAry as $time => $fieldValue) {
                                        $history[$time] = date('M j, Y H:i:s', $time);
                                    }

                                    $revision = new \Pop\Form\Element\Select('history_' . $modelId . '_' . $field->id, $history);
                                    $revision->setLabel('Select Revision');
                                    $revision->setAttribute('onchange', 'phire.changeHistory(this);');
                                    $revision->setAttribute('data-model', $fv->model);
                                    $controller->view()->form->insertElementAfter($key, $revision);
                                }
                                $controller->view()->form->{$key} = $value;
                            }
                        } else {
                            $fv = new Record();
                            $fv->setPrefix(DB_PREFIX)
                                ->setPrimaryKeys(['id'])
                                ->setTable('field_' . $field->name);

                            $fv->findRecordsBy(['model_id' => $modelId, 'model' => $model], ['order' => 'timestamp DESC']);
                            if (isset($fv->model_id)) {
                                $fieldValue = $fv->getColumns();
                                if ($field->isMultiple()) {
                                    $values = [];
                                    foreach ($fv->rows() as $v) {
                                        if ($v->timestamp == $fieldValue['timestamp']) {
                                            $values[] = $v->value;
                                        }
                                    }
                                    $value = $values;
                                } else {
                                    $value = $fieldValue['value'];
                                }

                                if ($field->encrypt) {
                                    $value = (new Mcrypt())->decrypt($value);
                                }

                                if ($field->type == 'file') {
                                    $rmCheckbox = new \Pop\Form\Element\CheckboxSet(
                                        'rm_field_file_' . $field->id, [$fieldValue['value'] => 'Remove <a href="' .
                                            $application->module('phire-fields')->config()['upload_folder'] . '/' .
                                            $fieldValue['value'] . '" target="_blank">' . $fieldValue['value'] . '</a>?']
                                    );
                                    $controller->view()->form->insertElementAfter($key, $rmCheckbox);
                                    $value = null;
                                }

                                if ((strpos($field->type, '-history') !== false) && ($fv->count() > 1)) {
                                    $history    = [0 => 'Current'];
                                    $historyAry = $fv->rows();
                                    for ($i = 1; $i < count($historyAry); $i++) {
                                        $history[$historyAry[$i]->timestamp] = date('M j, Y H:i:s', $historyAry[$i]->timestamp);
                                    }

                                    $revision = new \Pop\Form\Element\Select('history_' . $modelId . '_' . $field->id, $history);
                                    $revision->setLabel('Select Revision');
                                    $revision->setAttribute('onchange', 'phire.changeHistory(this);');
                                    $revision->setAttribute('data-model', $fv->model);
                                    $controller->view()->form->insertElementAfter($key, $revision);
                                }
                            }
                        }

                        $controller->view()->form->{$key} = $value;
                    }
                }
            }
        }
    }

    /**
     * Remove any media files
     *
     * @param  Application $application
     * @return void
     */
    public static function removeMedia(Application $application)
    {
        $uploadFolder = $application->module('phire-fields')->config()['upload_folder'];
        $mediaLibrary = $application->module('phire-fields')->config()['media_library'];
        if (($_POST) && isset($_POST['rm_media']) && (null !== $mediaLibrary) && ($application->isRegistered('phire-media'))) {
            $media    = new \Phire\Media\Model\Media();
            $fields   = Table\Fields::findBy(['type' => 'file']);
            $fieldIds = [];

            foreach ($fields->rows() as $field) {
                $fieldIds[$field->id] = $field->name;
            }

            foreach ($_POST['rm_media'] as $mid) {
                $media->getById($mid);
                if (isset($media->id) && !empty($media->file)) {
                    $sql = Table\FieldValues::sql();
                    $sql->select()->where('value LIKE :value');
                    $fv = Table\FieldValues::execute((string)$sql, ['value' => '%"' . $media->file . '"%']);

                    // Remove field value media files from EAV field table
                    if ($fv->count() > 0) {
                        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $media->file)) {
                            unlink($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $media->file);
                        }
                        foreach ($fv->rows() as $val) {
                            $v = json_decode($val->value);
                            if (is_array($v) && in_array($media->file, $v)) {
                                $sql = Table\FieldValues::sql();
                                $sql->select()
                                    ->where('field_id = :field_id')
                                    ->where('model_id = :model_id')
                                    ->where('value LIKE :value');
                                $f = Table\FieldValues::execute((string)$sql, [
                                    'field_id' => $val->field_id,
                                    'model_id' => $val->model_id,
                                    'value'    => '%"' . $media->file . '"%'
                                ]);
                                if (isset($f->field_id)) {
                                    unset($v[array_search($media->file, $v)]);
                                    if (count($v) > 0) {
                                        $v = array_values($v);
                                        $f->value = json_encode($v);
                                        $f->save();
                                    } else {
                                        $f->delete();
                                    }
                                }
                            } else {
                                $f = Table\FieldValues::findBy([
                                    'field_id' => $val->field_id,
                                    'model_id' => $val->model_id,
                                    'value' => '"' . $media->file . '"'
                                ]);
                                $f->delete();
                            }
                        }
                    }

                    // Remove field value media files from field tables
                    foreach ($fieldIds as $fieldId => $fieldName) {
                        $fv = new Record();
                        $fv->setPrefix(DB_PREFIX)
                            ->setPrimaryKeys(['id'])
                            ->setTable('field_' . $fieldName);

                        $sql = $fv->getSql();
                        $sql->select()->where('value LIKE :value');
                        $fv->executeStatement($sql, ['value' => '%"' . $media->file . '"%']);
                        if ($fv->count() > 0) {
                            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $media->file)) {
                                unlink($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $media->file);
                            }
                            foreach ($fv->rows() as $val) {
                                $f = new Record();
                                $f->setPrefix(DB_PREFIX)
                                    ->setPrimaryKeys(['id'])
                                    ->setTable('fields_' . $fieldName);
                                $f->findRecordById($val->id);
                                if (isset($f->id)) {
                                    $f->delete();
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Save dynamic field values
     *
     * @param  AbstractController $controller
     * @param  Application        $application
     * @return void
     */
    public static function save(AbstractController $controller, Application $application)
    {
        if (($_POST) && ($controller->hasView()) && (null !== $controller->view()->id) &&
            (null !== $controller->view()->form) && ($controller->view()->form instanceof \Pop\Form\Form)) {
            $fields       = $controller->view()->form->getFields();
            $modelId      = $controller->view()->id;
            $model        = str_replace('Form', 'Model', get_class($controller->view()->form));
            $uploadFolder = $application->module('phire-fields')->config()['upload_folder'];
            $mediaLibrary = $application->module('phire-fields')->config()['media_library'];

            // Remove any files
            foreach ($_POST as $key => $value) {
                if ((substr($key, 0, 14) == 'rm_field_file_') && isset($value[0])) {
                    $fieldId = substr($key, 14);
                    if (strpos($fieldId, '_') !== false) {
                        $fieldId = substr($fieldId, 0, strpos($fieldId, '_'));
                    }

                    $field = Table\Fields::findById($fieldId);
                    if (isset($field->id)) {
                        if ($field->storage == 'eav') {
                            $fv = Table\FieldValues::findById([$fieldId, $modelId, $model]);
                            if (isset($fv->field_id)) {
                                $oldValue = json_decode($fv->value);
                                if (is_array($oldValue)) {
                                    if (array_search($value[0], $oldValue) !== false) {
                                        $k = array_search($value[0], $oldValue);
                                        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $oldValue[$k])) {
                                            unlink($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $oldValue[$k]);
                                            if ((null !== $mediaLibrary) && ($application->isRegistered('phire-media'))) {
                                                $media = new \Phire\Media\Model\Media();
                                                $media->getByFile($oldValue[$k]);

                                                if (isset($media->id) && ($media->library_folder == $mediaLibrary)) {
                                                    $media->remove(['rm_media' => [$media->id]]);
                                                }
                                            }
                                        }
                                        unset($oldValue[$k]);
                                    }
                                    if (count($oldValue) == 0) {
                                        $fv->delete();
                                    } else {
                                        $oldValue = array_values($oldValue);
                                        $fv->value = json_encode($oldValue);
                                        $fv->save();
                                    }
                                } else {
                                    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $oldValue)) {
                                        unlink($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $oldValue);
                                        if ((null !== $mediaLibrary) && ($application->isRegistered('phire-media'))) {
                                            $media = new \Phire\Media\Model\Media();
                                            $media->getByFile($oldValue);

                                            if (isset($media->id) && ($media->library_folder == $mediaLibrary)) {
                                                $media->remove(['rm_media' => [$media->id]]);
                                            }
                                        }
                                    }
                                    $fv->delete();
                                }
                            }
                        } else {
                            $fv = new Record();
                            $fv->setPrefix(DB_PREFIX)
                                ->setPrimaryKeys(['id'])
                                ->setTable('field_' . $field->name);

                            $fv->findRecordsBy(['model_id' => $modelId, 'model' => $model, 'value' => $value[0]]);
                            if (isset($fv->model_id)) {
                                if (file_exists($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $value[0])) {
                                    unlink($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $value[0]);
                                    if ((null !== $mediaLibrary) && ($application->isRegistered('phire-media'))) {
                                        $media = new \Phire\Media\Model\Media();
                                        $media->getByFile($value[0]);

                                        if (isset($media->id) && ($media->library_folder == $mediaLibrary)) {
                                            $media->remove(['rm_media' => [$media->id]]);
                                        }
                                    }
                                }
                                $fv = new Record();
                                $fv->setPrefix(DB_PREFIX)
                                    ->setPrimaryKeys(['id'])
                                    ->setTable('field_' . $field->name);
                                $fv->delete(['model_id' => $modelId, 'model' => $model, 'value' => $value[0]]);
                            }
                        }
                    }
                }
            }

            // Save field values
            foreach ($fields as $key => $value) {
                if ((substr($key, 0, 6) == 'field_') && (substr_count($key, '_') == 1)) {
                    $fieldId = (int)substr($key, 6);
                    $field = Table\Fields::findById($fieldId);
                    if (isset($field->id)) {
                        if ($field->storage == 'eav') {
                            self::saveToEavTable(
                                $application, $field, $value, $model, $modelId, $uploadFolder, $mediaLibrary
                            );
                        } else {
                            self::saveToFieldTable(
                                $application, $field, $value, $model, $modelId, $uploadFolder, $mediaLibrary
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Save dynamic field values to the EAV table
     *
     * @param  Application        $application
     * @param  Table\Fields       $field
     * @param  mixed              $value
     * @param  string             $model
     * @param  int                $modelId
     * @param  string             $uploadFolder
     * @param  string             $mediaLibrary
     * @return void
     */
    protected static function saveToEavTable(
        Application $application, $field, $value, $model, $modelId, $uploadFolder = null, $mediaLibrary = null
    )
    {
        $dynamicFieldIds = [];
        $fieldId         = $field->id;
        $key             = 'field_' . $fieldId;

        if ($field->dynamic) {
            $dynamicFieldIds[] = $field->id;
        }

        $fv = Table\FieldValues::findById([$fieldId, $modelId, $model]);

        if (($field->type == 'file') && isset($_FILES[$key]) &&
            !empty($_FILES[$key]['tmp_name']) && !empty($_FILES[$key]['name'])) {
            if (isset($fv->field_id)) {
                $oldFile = json_decode($fv->value);
                if (file_exists($_SERVER['DOCUMENT_ROOT'] . $uploadFolder .'/' . $oldFile)) {
                    unlink($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $oldFile);
                }
            }

            if ((null !== $mediaLibrary) && ($application->isRegistered('phire-media'))) {
                $library = new \Phire\Media\Model\MediaLibrary();
                $library->getByFolder($mediaLibrary);
                if (isset($library->id)) {
                    $settings = $library->getSettings();
                    $mediaUpload = new Upload(
                        $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . CONTENT_PATH . '/' . $library->folder,
                        $settings['max_filesize'], $settings['disallowed_types'], $settings['allowed_types']
                    );
                    if ($mediaUpload->test($_FILES[$key])) {
                        $media = new \Phire\Media\Model\Media();
                        $media->save($_FILES[$key], ['library_id' => $library->id]);
                        $value = $media->file;
                        copy(
                            $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . CONTENT_PATH . '/' . $library->folder . '/' . $media->file,
                            $_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $media->file
                        );
                    }
                }
            } else {
                $upload = new Upload(
                    $_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/',
                    $application->module('phire-fields')->config()['max_size'],
                    $application->module('phire-fields')->config()['disallowed_types'],
                    $application->module('phire-fields')->config()['allowed_types']
                );
                $value = $upload->upload($_FILES[$key]);
            }
        }

        if (!empty($value) && ($value != ' ')) {
            if (($field->encrypt) && !is_array($value)) {
                $value = (new Mcrypt())->create($value);
            }
        }

        if (isset($fv->field_id)) {
            $oldValue = json_decode($fv->value, true);
            if (!empty($value) && ($value != ' ')) {
                if (strpos($field->type, '-history') !== false) {
                    if ($value != $oldValue) {
                        $ts = (null !== $fv->timestamp) ? $fv->timestamp : time() - 180;
                        if (null !== $fv->history) {
                            $history      = json_decode($fv->history, true);
                            $history[$ts] = $oldValue;
                            if (count($history) > $application->module('phire-fields')->config()['history']) {
                                $history = array_slice($history, 1, $application->module('phire-fields')->config()['history'], true);
                            }
                            $fv->history = json_encode($history);
                        } else {
                            $fv->history = json_encode([$ts => $oldValue]);
                        }
                    }
                }
                if (($field->dynamic) && is_array($oldValue) && isset($oldValue[0])) {
                    $oldValue[0] = $value;
                    $newValue    = json_encode($oldValue);
                } else {
                    $newValue = json_encode($value);
                }
                $fv->value     = $newValue;
                $fv->timestamp = time();
                $fv->save();
            } else if ((!$field->dynamic) && ($field->type != 'file')) {
                $fv->delete();
            } else if (($field->dynamic) && ($field->type != 'file') && is_array($oldValue) && isset($oldValue[0])) {
                $oldValue[0]   = '';
                $newValue      = json_encode($oldValue);
                $fv->value     = $newValue;
                $fv->timestamp = time();
                $fv->save();
            }
        } else {
            if (!empty($value) && ($value != ' ')) {
                $fv = new Table\FieldValues([
                    'field_id'  => $fieldId,
                    'model_id'  => $modelId,
                    'model'     => $model,
                    'value'     => ($field->dynamic) ? json_encode([$value]) : json_encode($value),
                    'timestamp' => time()
                ]);
                $fv->save();
            }
        }

        foreach ($dynamicFieldIds as $fieldId) {
            $i      = 1;
            $offset = 0;
            $fv     = Table\FieldValues::findById([$fieldId, $modelId, $model]);

            $checkValue = json_decode($fv->value, true);
            if (is_array($checkValue) && isset($checkValue[0]) && is_array($checkValue[0])) {
                foreach ($checkValue as $k => $v) {
                    $fieldToCheck = ($k > 0) ? 'field_' . $fieldId . '_' . $k : 'field_' . $fieldId;
                    if (!isset($_POST[$fieldToCheck])) {
                        unset($checkValue[$k]);
                    }
                }
                $checkValue = array_values($checkValue);
                $fv->value = json_encode($checkValue);
                $fv->timestamp = time();
                $fv->save();
            }

            while (isset($_POST['field_' . $fieldId . '_' . $i])) {
                if (!empty($_POST['field_' . $fieldId . '_' . $i]) && ($_POST['field_' . $fieldId . '_' . $i] != ' ')) {
                    $postValue = $_POST['field_' . $fieldId . '_' . $i];
                    if (isset($fv->field_id)) {
                        $value = json_decode($fv->value, true);
                        if (isset($value[$i - $offset])) {
                            $value[$i - $offset] = $postValue;
                        } else {
                            $value[] = $postValue;
                        }
                        $fv->value = json_encode($value);
                        $fv->timestamp = time();
                        $fv->save();
                    } else {
                        $fv = new Table\FieldValues([
                            'field_id'  => $fieldId,
                            'model_id'  => $modelId,
                            'model'     => $model,
                            'value'     => json_encode([$postValue]),
                            'timestamp' => time()
                        ]);
                        $fv->save();
                    }
                } else if (isset($fv->field_id)) {
                    $value = json_decode($fv->value, true);
                    if (isset($value[$i])) {
                        unset($value[$i]);
                        $value = array_values($value);
                        $offset++;
                    }
                    $fv->value = json_encode($value);
                    $fv->timestamp = time();
                    $fv->save();
                }
                $i++;
            }
        }

        foreach ($dynamicFieldIds as $fieldId) {
            $i      = 1;
            $offset = 0;
            $fv     = Table\FieldValues::findById([$fieldId, $modelId, $model]);

            while (isset($_FILES['field_' . $fieldId . '_' . $i])) {
                if (!empty($_FILES['field_' . $fieldId . '_' . $i]['tmp_name'])) {
                    if ((null !== $mediaLibrary) && ($application->isRegistered('phire-media'))) {
                        $library = new \Phire\Media\Model\MediaLibrary();
                        $library->getByFolder($mediaLibrary);
                        if (isset($library->id)) {
                            $settings = $library->getSettings();
                            $mediaUpload = new Upload(
                                $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . CONTENT_PATH . '/' . $library->folder,
                                $settings['max_filesize'], $settings['disallowed_types'], $settings['allowed_types']
                            );
                            if ($mediaUpload->test($_FILES['field_' . $fieldId . '_' . $i])) {
                                $media = new \Phire\Media\Model\Media();
                                $media->save($_FILES['field_' . $fieldId . '_' . $i], ['library_id' => $library->id]);
                                $postValue = $media->file;
                                copy(
                                    $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . CONTENT_PATH . '/' . $library->folder . '/' . $media->file,
                                    $_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $media->file
                                );
                            }
                        }
                    } else {
                        $upload = new Upload(
                            $_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/',
                            $application->module('phire-fields')->config()['max_size'], $application->module('phire-fields')->config()['allowed_types']
                        );
                        $postValue = $upload->upload($_FILES['field_' . $fieldId . '_' . $i]);
                    }

                    if (isset($fv->field_id)) {
                        $value = json_decode($fv->value, true);
                        if (isset($value[$i - $offset])) {
                            $value[$i - $offset] = $postValue;
                        } else {
                            $value[] = $postValue;
                        }
                        $fv->value     = json_encode($value);
                        $fv->timestamp = time();
                        $fv->save();
                    } else {
                        $fv = new Table\FieldValues([
                            'field_id'  => $fieldId,
                            'model_id'  => $modelId,
                            'model'     => $model,
                            'value'     => json_encode([$postValue]),
                            'timestamp' => time()
                        ]);
                        $fv->save();
                    }
                }
                $i++;
            }
        }

        foreach ($dynamicFieldIds as $fieldId) {
            $fv = Table\FieldValues::findById([$fieldId, $modelId, $model]);
            if (isset($fv->field_id)) {
                $value = json_decode($fv->value, true);
                if (is_array($value) && isset($value[0]) && is_array($value[0])) {
                    foreach ($value as $key => $val) {
                        if (is_array($val) && isset($val[0]) && (empty($val[0]) || ($val[0] == ' '))) {
                            unset($val[0]);
                            $value[$key] = array_values($val);
                            if (count($value[$key]) == 0) {
                                unset($value[$key]);
                            }
                        }
                    }
                    $value = array_values($value);
                } else if (is_array($value) && isset($value[0]) && (empty($value[0]) || ($value[0] == ' '))) {
                    unset($value[0]);
                    $value = array_values($value);
                }
                if (count($value) == 0) {
                    $fv->delete();
                } else {
                    $fv->value = json_encode($value);
                    $fv->save();
                }
            }
        }
    }

    /**
     * Save dynamic field values to a field table
     *
     * @param  Application        $application
     * @param  Table\Fields       $field
     * @param  mixed              $value
     * @param  string             $model
     * @param  int                $modelId
     * @param  string             $uploadFolder
     * @param  string             $mediaLibrary
     * @return void
     */
    protected static function saveToFieldTable(
        Application $application, $field, $value, $model, $modelId, $uploadFolder = null, $mediaLibrary = null
    )
    {
        $fieldId = $field->id;
        $key     = 'field_' . $fieldId;

        switch ($field->storage) {
            case 'int':
                $value = (int)$value;
                break;
            case 'float':
                $value = (float)$value;
                break;
            case 'date':
                $value = date('Y-m-d', strtotime($value));
                break;
            case 'time':
                $value = date('H:i:s', strtotime($value));
                break;
            case 'datetime':
                $value = date('Y-m-d H:i:s', strtotime($value));
                break;
        }

        $fv = new Record();
        $fv->setPrefix(DB_PREFIX)
            ->setPrimaryKeys(['id'])
            ->setTable('field_' . $field->name);
        $fv->findRecordsBy(['model_id' => $modelId, 'model' => $model], ['order' => 'timestamp DESC']);

        if (isset($fv->model_id)) {
            if (strpos($field->type, '-history') !== false) {
                $historyAry = $fv->rows();
                if ($historyAry[0]->value != $value) {
                    if ($fv->count() == $application->module('phire-fields')->config()['history']) {
                        $fv = new Record();
                        $fv->setPrefix(DB_PREFIX)
                            ->setPrimaryKeys(['id'])
                            ->setTable('field_' . $field->name);
                        $fv->delete([
                            'model_id'  => $modelId,
                            'model'     => $model,
                            'timestamp' => end($historyAry)->timestamp
                        ]);
                    }

                    $fv = new Record();
                    $fv->setPrefix(DB_PREFIX)
                        ->setPrimaryKeys(['id'])
                        ->setTable('field_' . $field->name);

                    $fv->findRecordsBy(['model_id' => $modelId, 'model' => $model]);
                    $fvRows = $fv->rows();
                    foreach ($fvRows as $f) {
                        $fv->findRecordById($f->id);
                        if (isset($fv)) {
                            $fv->revision = 1;
                            $fv->save();
                        }
                    }

                    $fv = new Record([
                        'model_id'  => $modelId,
                        'model'     => $model,
                        'timestamp' => time(),
                        'revision'  => 0,
                        'value'     => $value
                    ]);
                    $fv->setPrefix(DB_PREFIX)
                        ->setPrimaryKeys(['id'])
                        ->setTable('field_' . $field->name);
                    $fv->save();

                }
            } else {
                if ($field->type == 'file') {
                    self::saveFiles($fieldId, $modelId, $model, $field->encrypt, $application, $uploadFolder, $mediaLibrary);
                } else {
                    $fv->delete();
                    self::saveValues($fieldId, $modelId, $model, $value, $field->encrypt);
                }
            }
        } else {
            if (($field->type == 'file') && isset($_FILES[$key]) &&
                !empty($_FILES[$key]['tmp_name']) && !empty($_FILES[$key]['name'])) {
                self::saveFiles($fieldId, $modelId, $model, $field->encrypt, $application, $uploadFolder, $mediaLibrary);
            } else {
                self::saveValues($fieldId, $modelId, $model, $value, $field->encrypt);
            }
        }
    }

    /**
     * Delete dynamic field values
     *
     * @param  AbstractController $controller
     * @param  Application        $application
     *
     * @return void
     */
    public static function delete(AbstractController $controller, Application $application)
    {
        if ($_POST) {
            $uploadFolder = $application->module('phire-fields')->config()['upload_folder'];
            $mediaLibrary = $application->module('phire-fields')->config()['media_library'];

            foreach ($_POST as $key => $value) {
                if ((substr($key, 0, 3) == 'rm_') && is_array($value)) {
                    $fields       = Table\Fields::findBy();
                    $fieldIds     = [];
                    $fieldTypes   = [];
                    $fieldStorage = [];

                    foreach ($fields->rows() as $field) {
                        $fieldIds[$field->id]     = $field->name;
                        $fieldTypes[$field->id]   = $field->type;
                        $fieldStorage[$field->id] = $field->storage;
                    }

                    foreach ($value as $id) {
                        foreach ($fieldIds as $fieldId => $fieldName) {
                            if ($fieldStorage[$fieldId] == 'eav') {
                                $fv = Table\FieldValues::findBy(['model_id' => (int)$id]);
                                if ($fv->hasRows()) {
                                    foreach ($fv->rows() as $f) {
                                        $fValue = json_decode($f->value, true);
                                        if (!is_array($fValue)) {
                                            $fValue = [$fValue];
                                        }
                                        foreach ($fValue as $f) {
                                            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $f)) {
                                                unlink($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $f);
                                            }
                                            if ((null !== $mediaLibrary) && ($application->isRegistered('phire-media'))) {
                                                $library = new \Phire\Media\Model\MediaLibrary();
                                                $library->getByFolder($mediaLibrary);
                                                if (file_exists($_SERVER['DOCUMENT_ROOT'] . BASE_PATH . CONTENT_PATH . '/' . $library->folder . '/' . $f)) {
                                                    $media = new \Phire\Media\Model\Media();
                                                    $media->getByFile($f);

                                                    if (isset($media->id)) {
                                                        $media->remove(['rm_media' => [$media->id]]);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }

                                $fv = new Table\FieldValues();
                                $fv->delete(['model_id' => (int)$id]);
                            } else {
                                $fv = new Record();
                                $fv->setPrefix(DB_PREFIX)
                                    ->setPrimaryKeys(['id'])
                                    ->setTable('field_' . $fieldName);
                                $fv->findRecordsBy(['model_id' => (int)$id]);

                                if (($fieldTypes[$fieldId] == 'file') && ($fv->hasRows())) {
                                    foreach ($fv->rows() as $f) {
                                        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $f->value)) {
                                            unlink($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $f->value);
                                        }
                                        if ((null !== $mediaLibrary) && ($application->isRegistered('phire-media'))) {
                                            $library = new \Phire\Media\Model\MediaLibrary();
                                            $library->getByFolder($mediaLibrary);
                                            if (file_exists($_SERVER['DOCUMENT_ROOT'] . BASE_PATH . CONTENT_PATH . '/' . $library->folder . '/' . $f->value)) {
                                                $media = new \Phire\Media\Model\Media();
                                                $media->getByFile($f->value);

                                                if (isset($media->id)) {
                                                    $media->remove(['rm_media' => [$media->id]]);
                                                }
                                            }
                                        }
                                    }
                                }

                                $fv->delete(['model_id' => (int)$id]);
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
     * @param  int     $fieldId
     * @param  int     $modelId
     * @param  string  $model
     * @param  mixed   $value
     * @param  boolean $encrypt
     * @return void
     */
    protected static function saveValues($fieldId, $modelId, $model, $value, $encrypt)
    {
        $field = Table\Fields::findById($fieldId);
        if (isset($field->id)) {
            $time = time();
            if (!is_array($value)) {
                if ($encrypt) {
                    $value = (new Mcrypt())->create($value);
                }
                $value = [$value];
            }
            if (isset($_POST['field_' . $fieldId . '_1'])) {
                $i = 1;
                while (isset($_POST['field_' . $fieldId . '_' . $i])) {
                    if (!empty($_POST['field_' . $fieldId . '_' . $i])) {
                        $value[] = ($encrypt) ?
                            (new Mcrypt())->create($_POST['field_' . $fieldId . '_' . $i]) :
                            $_POST['field_' . $fieldId . '_' . $i];
                    }
                    $i++;
                }
            }
            foreach ($value as $v) {
                if (!empty($v)) {
                    $fv = new Record([
                        'model_id'  => $modelId,
                        'model'     => $model,
                        'timestamp' => $time,
                        'revision'  => 0,
                        'value'     => $v
                    ]);
                    $fv->setPrefix(DB_PREFIX)
                        ->setPrimaryKeys(['id'])
                        ->setTable('field_' . $field->name);
                    $fv->save();
                }
            }
        }
    }

    /**
     * Delete dynamic field files
     *
     * @param  int         $fieldId
     * @param  int         $modelId
     * @param  string      $model
     * @param  boolean     $encrypt
     * @param  Application $app
     * @param  string      $uploadFolder
     * @param  string      $mediaLibrary
     * @return void
     */
    protected static function saveFiles($fieldId, $modelId, $model, $encrypt, $app, $uploadFolder, $mediaLibrary = null)
    {
        $field = Table\Fields::findById($fieldId);
        if (isset($field->id)) {
            $time      = time();
            $newValues = [];

            $oldValues = new Record();
            $oldValues->setPrefix(DB_PREFIX)
                ->setPrimaryKeys(['id'])
                ->setTable('field_' . $field->name);

            $oldValues->findRecordsBy(['model_id' => $modelId, 'model' => $model], ['order' => 'id ASC']);
            $old = $oldValues->rows(false);

            foreach ($_FILES as $key => $file) {
                $id = (substr_count($key, '_') == 2) ? substr($key, (strrpos($key, '_') + 1)) : 0;

                if (!empty($_FILES[$key]['tmp_name']) && !empty($_FILES[$key]['name'])) {
                    if (null !== $mediaLibrary) {
                        $library = new \Phire\Media\Model\MediaLibrary();
                        $library->getByFolder($mediaLibrary);
                        if (isset($library->id)) {
                            $settings = $library->getSettings();
                            $mediaUpload = new Upload(
                                $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . CONTENT_PATH . '/' . $library->folder,
                                $settings['max_filesize'], $settings['disallowed_types'], $settings['allowed_types']
                            );
                            if ($mediaUpload->test($_FILES[$key])) {
                                $media = new \Phire\Media\Model\Media();
                                $media->save($_FILES[$key], ['library_id' => $library->id]);
                                $value = $media->file;
                                if ($encrypt) {
                                    $value = (new Mcrypt())->create($value);
                                }
                                if (isset($old[$id])) {
                                    $replaceValue = new Record();
                                    $replaceValue->setPrefix(DB_PREFIX)
                                        ->setPrimaryKeys(['id'])
                                        ->setTable('field_' . $field->name);

                                    $replaceValue->findRecordById($old[$id]['id']);
                                    if (isset($replaceValue->id)) {
                                        $replaceValue->value = $value;
                                        $replaceValue->save();

                                        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $old[$id]['value'])) {
                                            unlink($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $old[$id]['value']);
                                        }
                                        if (file_exists(
                                            $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . CONTENT_PATH . '/' . $library->folder . '/' . $old[$id]['value'])
                                        ) {
                                            $media = new \Phire\Media\Model\Media();
                                            $media->getByFile($old[$id]['value']);

                                            if (isset($media->id)) {
                                                $media->remove(['rm_media' => [$media->id]]);
                                            }
                                        }
                                    }
                                } else {
                                    $newValues[] = $value;
                                }
                                copy(
                                    $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . CONTENT_PATH . '/' . $library->folder . '/' . $media->file,
                                    $_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $media->file
                                );
                            }
                        }
                    } else {
                        $upload = new Upload(
                            $_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/',
                            $app->module('phire-fields')->config()['max_size'],
                            $app->module('phire-fields')->config()['disallowed_types'],
                            $app->module('phire-fields')->config()['allowed_types']
                        );
                        $value = $upload->upload($_FILES[$key]);
                        if ($encrypt) {
                            $value = (new Mcrypt())->create($value);
                        }
                        if (isset($old[$id])) {
                            $replaceValue = new Record();
                            $replaceValue->setPrefix(DB_PREFIX)
                                ->setPrimaryKeys(['id'])
                                ->setTable('field_' . $field->name);

                            $replaceValue->findRecordById($old[$id]['id']);
                            if (isset($replaceValue->id)) {
                                $replaceValue->value = $value;
                                $replaceValue->save();

                                if (file_exists($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $old[$id]['value'])) {
                                    unlink($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $old[$id]['value']);
                                }
                            }
                        } else {
                            $newValues[] = $value;
                        }
                    }
                }
            }

            foreach ($newValues as $v) {
                if (!empty($v)) {
                    $fv = new Record([
                        'model_id'  => $modelId,
                        'model'     => $model,
                        'timestamp' => $time,
                        'revision'  => 0,
                        'value'     => $v
                    ]);
                    $fv->setPrefix(DB_PREFIX)
                        ->setPrimaryKeys(['id'])
                        ->setTable('field_' . $field->name);
                    $fv->save();

                    $fvs = new Record();
                    $fvs->setPrefix(DB_PREFIX)
                        ->setPrimaryKeys(['id'])
                        ->setTable('field_' . $field->name);

                    $sql = $fvs->getSql();
                    $sql->update(['timestamp' => ':timestamp'])->where('model_id = :model_id')->where('model = :model');

                    $fvs->execute($sql, [
                        'timestamp' => $time,
                        'model_id'  => $modelId,
                        'model'     => $model
                    ]);
                }
            }
        }

    }

}