<?php

namespace App\Http\Controllers\CRUDBooster;

use crocodicstudio\crudbooster\controllers\CBController;

class TestCrudUuidController extends CBController
{
    public array $hookCalls = [];

    public string|int|null $lastAfterAddId = null;

    public function cbInit(): void
    {
        $this->table = 'test_crud_uuid_records';
        $this->primary_key = 'id';
        $this->title_field = 'name';
        $this->module_name = 'CRUD UUID Test';
        $this->form = [
            ['label' => 'ID', 'name' => 'id', 'type' => 'hidden'],
            ['label' => 'Name', 'name' => 'name', 'required' => true, 'validation' => 'required|min:3'],
        ];
    }

    public function hook_before_add(&$arr): void
    {
        $this->hookCalls[] = 'before_add';
    }

    public function hook_after_add($id): void
    {
        $this->hookCalls[] = 'after_add';
        $this->lastAfterAddId = $id;
    }
}
