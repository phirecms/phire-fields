<?php

namespace Fields\Controller;

use Fields\Model;
use Fields\Form;
use Fields\Table;
use Phire\Controller\AbstractController;
use Pop\Paginator\Paginator;

class GroupsController extends AbstractController
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

        $this->prepareView('fields/groups/index.phtml');
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
        $this->prepareView('fields/groups/add.phtml');
        $this->view->title = 'Fields : Groups : Add';

        $this->view->form = new Form\FieldGroup($this->application->config()['forms']['Fields\Form\FieldGroup']);

        if ($this->request->isPost()) {
            $this->view->form->addFilter('strip_tags')
                 ->addFilter('htmlentities', [ENT_QUOTES, 'UTF-8'])
                 ->setFieldValues($this->request->getPost());

            if ($this->view->form->isValid()) {
                $this->view->form->clearFilters()
                     ->addFilter('html_entity_decode', [ENT_QUOTES, 'UTF-8'])
                     ->filter();
                $group = new Model\FieldGroup();
                $group->save($this->view->form->getFields());
                $this->view->id = $group->id;
                $this->redirect(BASE_PATH . APP_URI . '/fields/groups/edit/' . $group->id . '?saved=' . time());
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
        $group = new Model\FieldGroup();
        $group->getById($id);

        $this->prepareView('fields/groups/edit.phtml');
        $this->view->title            = 'Fields : Groups';
        $this->view->field_group_name = $group->name;

        $fields = $this->application->config()['forms']['Fields\Form\FieldGroup'];
        $fields[1]['name']['attributes']['onkeyup'] = 'phire.changeTitle(this.value);';

        $this->view->form = new Form\FieldGroup($fields);
        $this->view->form->addFilter('htmlentities', [ENT_QUOTES, 'UTF-8'])
             ->setFieldValues($group->toArray());

        if ($this->request->isPost()) {
            $this->view->form->addFilter('strip_tags')
                 ->setFieldValues($this->request->getPost());

            if ($this->view->form->isValid()) {
                $this->view->form->clearFilters()
                     ->addFilter('html_entity_decode', [ENT_QUOTES, 'UTF-8'])
                     ->filter();
                $group = new Model\FieldGroup();
                $group->update($this->view->form->getFields());
                $this->view->id = $group->id;
                $this->redirect(BASE_PATH . APP_URI . '/fields/groups/edit/' . $group->id . '?saved=' . time());
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
            $group = new Model\FieldGroup();
            $group->remove($this->request->getPost());
        }
        $this->redirect(BASE_PATH . APP_URI . '/fields/groups?removed=' . time());
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
