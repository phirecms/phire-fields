<?php

namespace Phire\Fields\Event;

use Phire\Fields\Table;
use Pop\Application;
use Phire\Controller\AbstractController;
use Pop\Crypt\Mcrypt;
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

            foreach ($fields as $key => $value) {
                if (substr($key, 0, 6) == 'field_') {
                    $fieldId = (int)substr($key, 6);
                    $field   = Table\Fields::findById($fieldId);
                    if (isset($field->id)) {
                        $fv = Table\FieldValues::findById([$fieldId, $modelId]);
                        if (isset($fv->field_id)) {
                            $fieldValue = $fv->getColumns();
                            $value      = json_decode($fieldValue['value']);
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
     * Remove any media files
     *
     * @param  Application $application
     * @return void
     */
    public static function removeMedia(Application $application)
    {
        $uploadFolder = $application->module('phire-fields')->config()['upload_folder'];
        $mediaLibrary = $application->module('phire-fields')->config()['media_library'];
        if (($_POST) && isset($_POST['rm_media']) && (null !== $mediaLibrary) && ($application->isRegistered('Media'))) {
            $media = new \Media\Model\Media();
            foreach ($_POST['rm_media'] as $mid) {
                $media->getById($mid);
                if (isset($media->id) && !empty($media->file)) {
                    $sql = Table\FieldValues::getSql();
                    $sql->select()->where('value LIKE :value');
                    $fv = Table\FieldValues::execute((string)$sql, ['value' => '%"' . $media->file . '"%']);
                    if ($fv->count() > 0) {
                        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $media->file)) {
                            unlink($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $media->file);
                        }
                        foreach ($fv->rows() as $val) {
                            $v = json_decode($val->value);
                            if (is_array($v) && in_array($media->file, $v)) {
                                unset($v[array_search($media->file, $v)]);
                                $f = Table\FieldValues::findById([$val->field_id, $val->model_id]);
                                if (count($v) > 0) {
                                    $v = array_values($v);
                                    $f->value = json_encode($v);
                                    $f->save();
                                } else {
                                    $f->delete();
                                }
                            } else {
                                $f = Table\FieldValues::findById([$val->field_id, $val->model_id]);
                                $f->delete();
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
            $uploadFolder = $application->module('phire-fields')->config()['upload_folder'];
            $mediaLibrary = $application->module('phire-fields')->config()['media_library'];

            foreach ($_POST as $key => $value) {
                if ((substr($key, 0, 14) == 'rm_field_file_') && isset($value[0])) {
                    $i       = 0;
                    $fieldId = substr($key, 14);
                    if (strpos($fieldId, '_') !== false) {
                        $i       = substr($fieldId, (strrpos($fieldId, '_') + 1));
                        $fieldId = substr($fieldId, 0, strpos($fieldId, '_'));
                    }

                    $fv = Table\FieldValues::findById([$fieldId, $modelId]);
                    if (isset($fv->field_id)) {
                        $oldValue = json_decode($fv->value);
                        if (is_array($oldValue)) {
                            if (array_search($value[0], $oldValue) !== false) {
                                $k = array_search($value[0], $oldValue);
                                if (file_exists($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $oldValue[$k])) {
                                    unlink($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $oldValue[$k]);
                                    if ((null !== $mediaLibrary) && ($application->isRegistered('Media'))) {
                                        $media = new \Media\Model\Media();
                                        $media->getByFile($oldValue[$k]);

                                        if (isset($media->id) && ($media->library_folder == $mediaLibrary)) {
                                            $media->remove(['rm_media'=> [$media->id]]);
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
                                if ((null !== $mediaLibrary) && ($application->isRegistered('Media'))) {
                                    $media = new \Media\Model\Media();
                                    $media->getByFile($oldValue);

                                    if (isset($media->id) && ($media->library_folder == $mediaLibrary)) {
                                        $media->remove(['rm_media'=> [$media->id]]);
                                    }
                                }
                            }
                            $fv->delete();
                        }
                    }
                }
            }

            $dynamicFieldIds = [];
            foreach ($fields as $key => $value) {
                if ((substr($key, 0, 6) == 'field_') && (substr_count($key, '_') == 1)) {
                    $fieldId = (int)substr($key, 6);
                    $field   = Table\Fields::findById($fieldId);
                    if (isset($field->id)) {
                        if ($field->dynamic) {
                            $dynamicFieldIds[] = $field->id;
                        }

                        $fv = Table\FieldValues::findById([$fieldId, $modelId]);

                        if (($field->type == 'file') && isset($_FILES[$key]) &&
                            !empty($_FILES[$key]['tmp_name']) && !empty($_FILES[$key]['name'])) {
                            if (isset($fv->field_id)) {
                                $oldFile = json_decode($fv->value);
                                if (file_exists($_SERVER['DOCUMENT_ROOT'] . $uploadFolder .'/' . $oldFile)) {
                                    unlink($_SERVER['DOCUMENT_ROOT'] . $uploadFolder . '/' . $oldFile);
                                }
                            }

                            if ((null !== $mediaLibrary) && ($application->isRegistered('Media'))) {
                                $library = new \Media\Model\MediaLibrary();
                                $library->getByFolder($mediaLibrary);
                                if (isset($library->id)) {
                                    $settings = $library->getSettings();
                                    $mediaUpload = new Upload(
                                        $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . CONTENT_PATH . '/' . $library->folder,
                                        $settings['max_filesize'], $settings['disallowed_types'], $settings['allowed_types']
                                    );
                                    if ($mediaUpload->test($_FILES[$key])) {
                                        $media = new \Media\Model\Media();
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
                                    'value'     => ($field->dynamic) ? json_encode([$value]) : json_encode($value),
                                    'timestamp' => time()
                                ]);
                                $fv->save();
                            }
                        }
                    }
                }
            }

            foreach ($dynamicFieldIds as $fieldId) {
                $i      = 1;
                $offset = 0;
                $fv     = Table\FieldValues::findById([$fieldId, $modelId]);

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
                                'field_id' => $fieldId,
                                'model_id' => $modelId,
                                'value' => json_encode([$postValue]),
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
                $fv     = Table\FieldValues::findById([$fieldId, $modelId]);

                while (isset($_FILES['field_' . $fieldId . '_' . $i])) {
                    if (!empty($_FILES['field_' . $fieldId . '_' . $i]['tmp_name'])) {
                        if ((null !== $mediaLibrary) && ($application->isRegistered('Media'))) {
                            $library = new \Media\Model\MediaLibrary();
                            $library->getByFolder($mediaLibrary);
                            if (isset($library->id)) {
                                $settings = $library->getSettings();
                                $mediaUpload = new Upload(
                                    $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . CONTENT_PATH . '/' . $library->folder,
                                    $settings['max_filesize'], $settings['disallowed_types'], $settings['allowed_types']
                                );
                                if ($mediaUpload->test($_FILES['field_' . $fieldId . '_' . $i])) {
                                    $media = new \Media\Model\Media();
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
                $fv = Table\FieldValues::findById([$fieldId, $modelId]);
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