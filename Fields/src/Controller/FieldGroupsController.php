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

    }

    /**
     * Add action method
     *
     * @return void
     */
    public function add()
    {

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
