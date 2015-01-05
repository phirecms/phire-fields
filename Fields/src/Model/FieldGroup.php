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

    }

    /**
     * Get field group by ID
     *
     * @param  int $id
     * @return void
     */
    public function getById($id)
    {

    }

    /**
     * Save new field group
     *
     * @param  array $fields
     * @return void
     */
    public function save(array $fields)
    {

    }

    /**
     * Update an existing field group
     *
     * @param  array $fields
     * @return void
     */
    public function update(array $fields)
    {

    }

    /**
     * Remove a field group
     *
     * @param  array $fields
     * @return void
     */
    public function remove(array $fields)
    {
        if (isset($fields['rm_fields'])) {
            foreach ($fields['rm_fields'] as $id) {
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
