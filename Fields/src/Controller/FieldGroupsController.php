<?php

namespace Fields\Controller;

use Fields\Model;
use Fields\Form;
use Fields\Table;
use Phire\Controller\AbstractController;
use Pop\Http\Response;
use Pop\Paginator\Paginator;

class FieldGroupsController extends AbstractController
{

    /**
     * Index action method
     *
     * @return void
     */
    public function index()
    {
        $group = new Model\FieldGroup();

        if ($group->hasPages($this->config->pagination)) {
            $limit = $this->config->pagination;
            $pages = new Paginator($group->getCount(), $limit);
            $pages->useInput(true);
        } else {
            $limit = null;
            $pages = null;
        }

        $this->prepareView('groups/index.phtml');
        $this->view->title  = 'Fields : Groups';
        $this->view->pages  = $pages;
        $this->view->groups = $group->getAll(
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
        $this->prepareView('groups/add.phtml');
        $this->view->title = 'Fields : Groups : Add';

        $form = new Form\FieldGroup();

        if ($this->request->isPost()) {
            $form->addFilter('strip_tags')
                 ->addFilter('htmlentities', [ENT_QUOTES, 'UTF-8'])
                 ->setFieldValues($this->request->getPost());

            if ($form->isValid()) {
                $form->clearFilters()
                     ->addFilter('html_entity_decode', [ENT_QUOTES, 'UTF-8'])
                     ->filter();
                $group = new Model\FieldGroup();
                $group->save($form->getFields());

                Response::redirect(BASE_PATH . APP_URI . '/fields/groups/edit/' . $group->id . '?saved=' . time());
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
        $group = new Model\FieldGroup();
        $group->getById($id);

        $this->prepareView('groups/edit.phtml');
        $this->view->title = 'Fields : Groups : Edit : ' . $group->name;

        $form = new Form\FieldGroup();
        $form->addFilter('htmlentities', [ENT_QUOTES, 'UTF-8'])
             ->setFieldValues($group->toArray());

        if ($this->request->isPost()) {
            $form->addFilter('strip_tags')
                 ->setFieldValues($this->request->getPost());

            if ($form->isValid()) {
                $form->clearFilters()
                     ->addFilter('html_entity_decode', [ENT_QUOTES, 'UTF-8'])
                     ->filter();
                $group = new Model\FieldGroup();
                $group->update($form->getFields());

                Response::redirect(BASE_PATH . APP_URI . '/fields/groups/edit/' . $group->id . '?saved=' . time());
                exit();
            }
        }

        $this->view->form = $form;
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
            $group = new Model\FieldGroup();
            $group->remove($this->request->getPost());
        }
        Response::redirect(BASE_PATH . APP_URI . '/fields/groups?removed=' . time());
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
