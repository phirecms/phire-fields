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
namespace Phire\Fields\Controller;

use Phire\Fields\Model;
use Phire\Fields\Form;
use Phire\Fields\Table;
use Phire\Controller\AbstractController;
use Pop\Db\Record;
use Pop\Paginator\Paginator;

/**
 * Fields Index Controller class
 *
 * @category   Phire\Fields
 * @package    Phire\Fields
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.phirecms.org/license     New BSD License
 * @version    1.0.0
 */
class IndexController extends AbstractController
{

    /**
     * Index action method
     *
     * @return void
     */
    public function index()
    {
        $field = new Model\Field();

        if ($field->hasPages($this->config->pagination)) {
            $limit = $this->config->pagination;
            $pages = new Paginator($field->getCount(), $limit);
            $pages->useInput(true);
        } else {
            $limit = null;
            $pages = null;
        }

        $this->prepareView('fields/index.phtml');
        $this->view->title  = 'Fields';
        $this->view->pages  = $pages;
        $this->view->fields = $field->getAll(
            $limit, $this->request->getQuery('page'), $this->request->getQuery('sort')
        );

        $this->send();
    }

    /**
     * Add action method
     *
     * @return void
     */
    public function add()
    {
        $this->prepareView('fields/add.phtml');
        $this->view->title = 'Fields : Add';

        $fields = $this->application->config()['forms']['Phire\Fields\Form\Field'];

        if (!file_exists(getcwd() . CONTENT_PATH . '/modules/phire/assets/js/ckeditor')) {
            unset($fields[1]['editor']['value']['ckeditor-local']);
        }
        if (!file_exists(getcwd() . CONTENT_PATH . '/modules/phire/assets/js/tinymce')) {
            unset($fields[1]['editor']['value']['tinymce-local']);
        }

        $groups = Table\FieldGroups::findAll();
        foreach ($groups->rows() as $group) {
            $fields[0]['group_id']['value'][$group->id] = $group->name;
        }

        $models = $this->application->module('phire-fields')->config()['models'];
        foreach ($models as $model => $type) {
            $fields[4]['model_1']['value'][$model] = $model;
        }

        $this->view->form = new Form\Field($fields);

        if ($this->request->isPost()) {
            $this->view->form->addFilter('strip_tags')
                 ->addFilter('htmlentities', [ENT_QUOTES, 'UTF-8'])
                 ->setFieldValues($this->request->getPost());

            if ($this->view->form->isValid()) {
                $this->view->form->clearFilters()
                     ->addFilter('html_entity_decode', [ENT_QUOTES, 'UTF-8'])
                     ->filter();
                $field = new Model\Field();
                $field->save($this->view->form->getFields());
                $this->view->id = $field->id;
                $this->sess->setRequestValue('saved', true);
                $this->redirect(BASE_PATH . APP_URI . '/fields/edit/' . $field->id);
            }
        }

        $this->send();
    }

    /**
     * Edit action method
     *
     * @param  int $id
     * @return void
     */
    public function edit($id)
    {
        $field = new Model\Field();
        $field->getById($id);

        $fields = $this->application->config()['forms']['Phire\Fields\Form\Field'];

        if (!file_exists(getcwd() . CONTENT_PATH . '/modules/phire/assets/js/ckeditor')) {
            unset($fields[1]['editor']['value']['ckeditor-local']);
        }
        if (!file_exists(getcwd() . CONTENT_PATH . '/modules/phire/assets/js/tinymce')) {
            unset($fields[1]['editor']['value']['tinymce-local']);
        }
        if (null !== $field->editor) {
            $fields[1]['editor']['attributes']['style'] = 'display: block;';
        }

        $groups = Table\FieldGroups::findAll();
        foreach ($groups->rows() as $group) {
            $fields[0]['group_id']['value'][$group->id] = $group->name;
        }

        $models = $this->application->module('phire-fields')->config()['models'];
        foreach ($models as $model => $type) {
            $fields[4]['model_1']['value'][$model] = $model;
        }

        $this->prepareView('fields/edit.phtml');
        $this->view->title      = 'Fields : Edit';
        $this->view->field_name = $field->name;

        $fields[2]['name']['attributes']['onkeyup'] = 'phire.changeTitle(this.value);';

        $this->view->form = new Form\Field($fields);
        $this->view->form->addFilter('htmlentities', [ENT_QUOTES, 'UTF-8'])
             ->setFieldValues($field->toArray());

        if ($this->request->isPost()) {
            $this->view->form->addFilter('strip_tags')
                 ->setFieldValues($this->request->getPost());

            if ($this->view->form->isValid()) {
                $this->view->form->clearFilters()
                     ->addFilter('html_entity_decode', [ENT_QUOTES, 'UTF-8'])
                     ->filter();
                $field = new Model\Field();
                $field->update($this->view->form->getFields());
                $this->view->id = $field->id;
                $this->sess->setRequestValue('saved', true);
                $this->redirect(BASE_PATH . APP_URI . '/fields/edit/' . $field->id);
            }
        }

        $this->send();
    }

    /**
     * Remove action method
     *
     * @return void
     */
    public function remove()
    {
        if ($this->request->isPost()) {
            $field = new Model\Field();
            $field->remove($this->request->getPost(), $this->application->module('phire-fields')->config());
        }
        $this->sess->setRequestValue('removed', true);
        $this->redirect(BASE_PATH . APP_URI . '/fields');
    }

    /**
     * JSON models action method
     *
     * @param  mixed $model
     * @param  mixed $fid
     * @param  mixed $marked
     * @return void
     */
    public function json($model = null, $fid = null, $marked = null)
    {
        $json = [];

        // Get field validators and models
        if (($model == 0) && (null !== $fid)) {
            $field = Table\Fields::findById($fid);
            if (isset($field->id)) {
                $json['validators'] = (null != $field->validators) ? unserialize($field->validators) : [];
                $json['models']     = (null != $field->models)     ? unserialize($field->models)     : [];
            }
        // Get field values
        } else if ((null !== $fid) && (null == $marked) && (null !== $this->request->getQuery('model'))) {
            $field = Table\Fields::findById($fid);
            if ($field->dynamic) {
                if ($field->storage == 'eav') {
                    $fv = Table\FieldValues::findById([$fid, $model, $this->request->getQuery('model')]);
                    if (!empty($fv->value)) {
                        $values = json_decode($fv->value, true);
                        if (is_array($values)) {
                            array_shift($values);
                        }
                    } else {
                        $values = [];
                    }
                } else {
                    $fv = new Record();
                    $fv->setPrefix(DB_PREFIX)
                        ->setPrimaryKeys(['id'])
                        ->setTable('field_' . $field->name);

                    $fv->findRecordsBy([
                        'model_id'  => $model,
                        'model'     => $this->request->getQuery('model')
                    ], ['order' => 'id ASC']);

                    $values = [];
                    if ($fv->hasRows() && ($fv->count() > 1)) {
                        $rows = $fv->rows();

                        for ($i = 1; $i < count($rows); $i++) {
                            $values[] = $rows[$i]->value;
                        }
                    }
                }
                $json['values'] = $values;
            }
        // Get field history values
        } else if ((null !== $fid) && (null !== $marked) && (null !== $this->request->getQuery('model'))) {
            $field = Table\Fields::findById($fid);
            $value = '';

            if (isset($field->id)) {
                if ($field->storage == 'eav') {
                    $fv = Table\FieldValues::findById([$fid, $model, $this->request->getQuery('model')]);
                    if (isset($fv->field_id) && (null !== $fv->history)) {
                        $history = json_decode($fv->history, true);
                        if (isset($history[$marked])) {
                            $value = $history[$marked];
                            $f = Table\Fields::findById($fid);
                            if ($f->encrypt) {
                                $value = (new \Pop\Crypt\Mcrypt())->decrypt($value);
                            }
                        }
                    }
                    $json['fieldId'] = $fid;
                    $json['modelId'] = $model;
                    $json['model']   = $this->request->getQuery('model');
                    $json['value']   = $value;
                } else {
                    $fv = new Record();
                    $fv->setPrefix(DB_PREFIX)
                        ->setPrimaryKeys(['id'])
                        ->setTable('field_' . $field->name);

                    $fv->findRecordsBy([
                        'model_id' => $model,
                        'model' => $this->request->getQuery('model'),
                        'timestamp' => $marked
                    ], ['order' => 'id ASC']);

                    if (isset($fv->model_id)) {
                        $value = $fv->value;
                        if ($field->encrypt) {
                            $value = (new \Pop\Crypt\Mcrypt())->decrypt($value);
                        }
                    }

                    $json['fieldId'] = $fid;
                    $json['modelId'] = $model;
                    $json['model']   = $this->request->getQuery('model');
                    $json['value']   = $value;
                }
            }
        // Get field models
        } else {
            $model  = rawurldecode($model);
            $models = $this->application->module('phire-fields')->config()['models'];

            if (isset($models[$model])) {
                $json = $models[$model];
            }
        }

        $this->response->setBody(json_encode($json, JSON_PRETTY_PRINT));
        $this->send(200, ['Content-Type' => 'application/json']);
    }

    /**
     * Browser action
     *
     * @return void
     */
    public function browser()
    {
        if ((null !== $this->request->getQuery('editor')) && (null !== $this->request->getQuery('type'))) {
            $this->prepareView('fields/browser.phtml');
            $this->view->title = 'File Browser';

            if (null === $this->request->getQuery('asset')) {
                if ($this->request->getQuery('type') == 'image') {
                    $this->view->pages      = null;
                    $this->view->libraries  = [
                        'Assets' => ['images' => 'Images']
                    ];
                } else {
                    $libraries = [];
                    if ($this->application->isRegistered('phire-content')) {
                        $types = \Phire\Content\Table\ContentTypes::findAll(['order' => 'order ASC']);
                        if ($types->hasRows()) {
                            $libraries['Assets'] = [];
                            foreach ($types->rows() as $type) {
                                $libraries['Assets'][$type->id] = $type->name;
                            }
                        }
                    }
                    $libraries['Assets']['files']  = 'Files';
                    $libraries['Assets']['images'] = 'Images';
                    $this->view->pages             = null;
                    $this->view->libraries         = $libraries;
                }
            } else {
                $asset  = $this->request->getQuery('asset');
                $assets = [];
                $limit  = $this->config->pagination;
                $page   = $this->request->getQuery('page');
                $pages  = null;
                $field  = new Model\Field();

                $uploadFolder = BASE_PATH . CONTENT_PATH . '/files';

                switch ($asset) {
                    case ('files'):
                        $assets = $field->getAllFiles($uploadFolder);
                        $this->view->assetType = 'Files';
                        break;
                    case ('images'):
                        $assets = $field->getAllImages($uploadFolder);
                        $this->view->assetType = 'Images';
                        break;
                    default:
                        if (is_numeric($asset) && ($this->application->isRegistered('phire-content'))) {
                            $type    = \Phire\Content\Table\ContentTypes::findById($asset);
                            $content = \Phire\Content\Table\Content::findBy(['type_id' => $asset], ['order' => 'order, id ASC']);
                            foreach ($content->rows() as $c) {
                                $assets[BASE_PATH . $c->uri] = $c->title;
                            }

                            if (isset($type->id)) {
                                $this->view->assetType = $type->name;
                            }
                        }
                        break;
                }

                if (count($assets) > $limit) {
                    $pages  = new Paginator(count($assets), $limit);
                    $pages->useInput(true);
                    $offset = ((null !== $page) && ((int)$page > 1)) ?
                        ($page * $limit) - $limit : 0;
                    $assets = array_slice($assets, $offset, $limit, true);
                }

                $this->view->pages         = $pages;
                $this->view->browserAssets = $assets;
            }

            $this->send();
        }
    }

    /**
     * Prepare view
     *
     * @param  string $template
     * @return void
     */
    protected function prepareView($template)
    {
        $this->viewPath = __DIR__ . '/../../view';
        parent::prepareView($template);
    }

}
