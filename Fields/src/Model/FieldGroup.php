<?php

namespace Fields\Model;

use Phire\Model\AbstractModel;
use Fields\Table;

class FieldGroup extends AbstractModel
{

    /**
     * Get all field groups
     *
     * @param  int    $limit
     * @param  int    $page
     * @param  string $sort
     * @return array
     */
    public function getAll($limit = null, $page = null, $sort = null)
    {
        $order = $this->getSortOrder($sort, $page);

        if (null !== $limit) {
            $page = ((null !== $page) && ((int)$page > 1)) ?
                ($page * $limit) - $limit : null;

            return Table\FieldGroups::findAll(null, [
                'offset' => $page,
                'limit'  => $limit,
                'order'  => $order
            ])->rows();
        } else {
            return Table\FieldGroups::findAll(null, [
                'order'  => $order
            ])->rows();
        }
    }

    /**
     * Get field group by ID
     *
     * @param  int $id
     * @return void
     */
    public function getById($id)
    {
        $group = Table\FieldGroups::findById((int)$id);
        if (isset($group->id)) {
            $this->data['id']      = $group->id;
            $this->data['name']    = $group->name;
            $this->data['order']   = $group->order;
            $this->data['dynamic'] = $group->dynamic;
        }
    }

    /**
     * Save new field group
     *
     * @param  array $fields
     * @return void
     */
    public function save(array $fields)
    {
        $group = new Table\FieldGroups([
            'name'    => $fields['name'],
            'order'   => (int)$fields['order'],
            'dynamic' => (int)$fields['dynamic']
        ]);
        $group->save();

        $this->data = array_merge($this->data, $group->getColumns());
    }

    /**
     * Update an existing field group
     *
     * @param  array $fields
     * @return void
     */
    public function update(array $fields)
    {
        $group = Table\FieldGroups::findById((int)$fields['id']);
        if (isset($group->id)) {
            $group->name    = $fields['name'];
            $group->order   = (int)$fields['order'];
            $group->dynamic = (int)$fields['dynamic'];
            $group->save();

            $this->data = array_merge($this->data, $group->getColumns());
        }
    }

    /**
     * Remove a field group
     *
     * @param  array $fields
     * @return void
     */
    public function remove(array $fields)
    {
        if (isset($fields['rm_groups'])) {
            foreach ($fields['rm_groups'] as $id) {
                $field = Table\FieldGroups::findById((int)$id);
                if (isset($field->id)) {
                    $field->delete();
                }
            }
        }
    }

    /**
     * Determine if list of field groups has pages
     *
     * @param  int $limit
     * @return boolean
     */
    public function hasPages($limit)
    {
        return (Table\FieldGroups::findAll()->count() > $limit);
    }

    /**
     * Get count of field groups
     *
     * @return int
     */
    public function getCount()
    {
        return Table\FieldGroups::findAll()->count();
    }

}
