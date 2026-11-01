<?php

namespace App\Http\Controllers\CRUDBooster;

use crocodicstudio\crudbooster\controllers\CBController;

class TestCrudController extends CBController
{
    /** @var array<int, string> */
    public array $hookCalls = [];

    /** @var array<int, array{0: mixed, 1: mixed}> */
    public array $selectedActionCalls = [];

    public function cbInit(): void
    {
        $this->table = 'test_crud_records';
        $this->primary_key = 'id';
        $this->title_field = 'name';
        $this->module_name = 'CRUD Test';
        $this->button_import = true;
        $this->button_export = true;
        $this->col = [
            ['label' => 'Name', 'name' => 'name', 'str_limit' => 0, 'nl2br' => false, 'image' => false, 'download' => false],
            ['label' => 'Status', 'name' => 'status', 'str_limit' => 0, 'nl2br' => false, 'image' => false, 'download' => false],
        ];
        $this->form = [
            ['label' => 'Name', 'name' => 'name', 'required' => true, 'validation' => 'required|min:3'],
            ['label' => 'Status', 'name' => 'status', 'type' => 'text'],
            [
                'label' => 'Categories',
                'name' => 'categories',
                'type' => 'checkbox',
                'datatable' => 'test_crud_categories,name',
                'relationship_table' => 'test_crud_record_category',
            ],
            [
                'label' => 'Notes',
                'name' => 'notes',
                'type' => 'child',
                'table' => 'test_crud_notes',
                'foreign_key' => 'record_id',
                'columns' => [
                    ['name' => 'body', 'label' => 'Body', 'type' => 'text', 'validation' => 'required'],
                ],
            ],
            ['label' => 'Photo', 'name' => 'photo', 'type' => 'upload'],
        ];
    }

    public function hook_query_index(&$query): void
    {
        $this->hookCalls[] = 'query_index';
    }

    public function hook_row_index($index, &$value): void
    {
        $this->hookCalls[] = 'row_index';
    }

    public function hook_before_add(&$arr): void
    {
        $this->hookCalls[] = 'before_add';
        $arr['status'] = $arr['status'] ?: 'draft';
    }

    public function hook_after_add($id): void
    {
        $this->hookCalls[] = 'after_add';
    }

    public function hook_before_edit(&$arr, $id): void
    {
        $this->hookCalls[] = 'before_edit';
    }

    public function hook_after_edit($id): void
    {
        $this->hookCalls[] = 'after_edit';
    }

    public function hook_before_delete($id): void
    {
        $this->hookCalls[] = 'before_delete';
    }

    public function hook_after_delete($id): void
    {
        $this->hookCalls[] = 'after_delete';
    }

    public function actionButtonSelected($id_selected, $button_name): bool
    {
        $this->selectedActionCalls[] = [$id_selected, $button_name];

        return true;
    }
}
