<?php

namespace Fields\Controller;

use Fields\Model;
use Fields\Form;
use Fields\Table;
use Phire\Controller\AbstractController;
use Pop\Http\Response;
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

        $form = new Form\Field();

        if ($this->request->isPost()) {
            $form->addFilter('strip_tags')
                 ->addFilter('htmlentities', [ENT_QUOTES, 'UTF-8'])
                 ->setFieldValues($this->request->getPost());

            if ($form->isValid()) {
                $form->clearFilters()
                     ->addFilter('html_entity_decode', [ENT_QUOTES, 'UTF-8'])
                     ->filter();
                $field = new Model\Field();
                $field->save($form->getFields());

                Response::redirect(BASE_PATH . APP_URI . '/fields/edit/' . $field->id . '?saved=' . time());
                exit();
            }
        }

        $this->view->form = $form;
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
        Response::redirect(BASE_PATH . APP_URI . '/fields?removed=' . time());
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
