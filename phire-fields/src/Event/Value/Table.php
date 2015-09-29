<?php

namespace Phire\Fields\Event\Value;

use Phire\Fields\Table as T;
use Pop\Application;
use Pop\Crypt\Mcrypt;
use Pop\Db\Record;
use Pop\File\Upload;

class Table
{

    /**
     * Save dynamic field values to a field table
     *
     * @param  Application $application
     * @param  T\Fields    $field
     * @param  mixed       $value
     * @param  string      $model
     * @param  int         $modelId
     * @param  string      $uploadFolder
     * @param  string      $mediaLibrary
     * @return void
     */
    public static function save(
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
     * @param  int     $fieldId
     * @param  int     $modelId
     * @param  string  $model
     * @param  mixed   $value
     * @param  boolean $encrypt
     * @return void
     */
    protected static function saveValues($fieldId, $modelId, $model, $value, $encrypt)
    {
        $field = T\Fields::findById($fieldId);
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
        $field = T\Fields::findById($fieldId);
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