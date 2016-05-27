<?php
/**
 * Phire Fields Module
 *
 * @link       https://github.com/phirecms/phire-fields
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.phirecms.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Phire\Fields\Event\Value;

use Phire\Fields\Table;
use Pop\Application;
use Pop\Crypt\Mcrypt;
use Pop\File\Upload;

/**
 * Field Value EAV Event class
 *
 * @category   Phire\Fields
 * @package    Phire\Fields
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.phirecms.org/license     New BSD License
 * @version    1.0.0
 */
class Eav
{

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
    public static function save(
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

}