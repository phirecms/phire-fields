<?php

namespace Phire\Fields\Event;

use Phire\Fields\Table;
use Pop\Application;
use Phire\Controller\AbstractController;
use Pop\Crypt\Mcrypt;
use Pop\Db\Record;

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
                            Value\Eav::save(
                                $application, $field, $value, $model, $modelId, $uploadFolder, $mediaLibrary
                            );
                        } else {
                            Value\Table::save(
                                $application, $field, $value, $model, $modelId, $uploadFolder, $mediaLibrary
                            );
                        }
                    }
                }
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

}