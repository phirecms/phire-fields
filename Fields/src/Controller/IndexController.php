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

        $this->view->form = new Form\Field(
            $this->application->module('Fields')['models'], [], [],
            $this->application->config()['forms']['Fields\Form\Field']
        );

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

        $this->prepareView('edit.phtml');
        $this->view->title = 'Fields : Edit : ' . $field->name;

        $this->view->form = new Form\Field(
            $this->application->module('Fields')['models'], $field->validators,
            $field->models, $this->application->config()['forms']['Fields\Form\Field']
        );
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
            $field->remove($this->request->getPost());
        }
        $this->redirect(BASE_PATH . APP_URI . '/fields?removed=' . time());
    }

    /**
     * JSON action method
     *
     * @param  string $model
     * @return void
     */
    public function json($model)
    {
        $json   = [];
        $model  = rawurldecode($model);
        $models = $this->application->module('Fields')['models'];

        if (isset($models[$model])) {
            $json = $models[$model];
        }

        $this->response->setBody(json_encode($json, JSON_PRETTY_PRINT));
        $this->send(200, ['Content-Type' => 'application/json']);
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
