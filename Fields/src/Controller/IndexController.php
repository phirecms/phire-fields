<?php

namespace Fields\Controller;

use Fields\Model;
use Fields\Form;
use Fields\Table;
use Phire\Controller\AbstractController;
use Pop\Paginator\Paginator;

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

        $this->prepareView('index.phtml');
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
        $this->prepareView('add.phtml');
        $this->view->title = 'Fields : Add';

        $fields = $this->application->config()['forms']['Fields\Form\Field'];

        if (file_exists(getcwd() . CONTENT_PATH . '/modules/phire/assets/js/ckeditor')) {
            $fields[1]['editor']['value']['ckeditor'] = 'CKEditor';
        }
        if (file_exists(getcwd() . CONTENT_PATH . '/modules/phire/assets/js/tinymce')) {
            $fields[1]['editor']['value']['tinymce'] = 'TinyMCE';
        }

        $groups = Table\FieldGroups::findAll();
        foreach ($groups->rows() as $group) {
            $fields[0]['group_id']['value'][$group->id] = $group->name;
        }

        $models = $this->application->module('Fields')->config()['models'];
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
                $this->redirect(BASE_PATH . APP_URI . '/fields/edit/' . $field->id . '?saved=' . time());
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

        $fields = $this->application->config()['forms']['Fields\Form\Field'];

        if (file_exists(getcwd() . CONTENT_PATH . '/modules/phire/assets/js/ckeditor')) {
            $fields[1]['editor']['value']['ckeditor'] = 'CKEditor';
        }
        if (file_exists(getcwd() . CONTENT_PATH . '/modules/phire/assets/js/tinymce')) {
            $fields[1]['editor']['value']['tinymce'] = 'TinyMCE';
        }
        if (null !== $field->editor) {
            $fields[1]['editor']['attributes']['style'] = 'display: block;';
        }

        $groups = Table\FieldGroups::findAll();
        foreach ($groups->rows() as $group) {
            $fields[0]['group_id']['value'][$group->id] = $group->name;
        }

        $models = $this->application->module('Fields')->config()['models'];
        foreach ($models as $model => $type) {
            $fields[4]['model_1']['value'][$model] = $model;
        }

        $this->prepareView('edit.phtml');
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
                $this->redirect(BASE_PATH . APP_URI . '/fields/edit/' . $field->id . '?saved=' . time());
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
            $field->remove($this->request->getPost(), $this->application->module('Fields')->config());
        }
        $this->redirect(BASE_PATH . APP_URI . '/fields?removed=' . time());
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
        } else if ((null !== $fid) && (null == $marked)) {
            $fv = Table\FieldValues::findById([$fid, $model]);
            if (!empty($fv->value)) {
                $values = json_decode($fv->value, true);
                if (is_array($values)) {
                    array_shift($values);
                }
            } else {
                $values = [];
            }
            $json['values'] = $values;
        // Get field history values
        } else if ((null !== $fid) && (null !== $marked)) {
            $value = '';
            $fv = Table\FieldValues::findById([$fid, $model]);

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
            $json['value']   = $value;
        // Get field models
        } else {
            $model  = rawurldecode($model);
            $models = $this->application->module('Fields')->config()['models'];

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
            $uploadFolder = $this->application->module('Fields')->config()['upload_folder'];
            $field        = new Model\Field();

            if ($field->hasFiles($uploadFolder, $this->config->pagination)) {
                $limit = $this->config->pagination;
                $pages = new Paginator($field->getFileCount($uploadFolder), $limit);
                $pages->useInput(true);
            } else {
                $limit = null;
                $pages = null;
            }

            $this->prepareView('browser.phtml');
            $this->view->title = 'File Browser';
            $this->view->pages = $pages;
            $this->view->files = $field->getAllFiles($uploadFolder, $limit, $this->request->getQuery('page'));

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
