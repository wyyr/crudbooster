<?php

namespace App\Http\Controllers\CRUDBooster;

use crocodicstudio\crudbooster\controllers\CBController;

class TestRelationController extends CBController
{
    public function getDeleteLegacy(): void
    {
        abort(404);
    }

    public function cbInit(): void
    {
        $this->table = 'test_relation_modules';
        $this->form = [
            [
                'name' => 'children',
                'type' => 'child',
                'columns' => [
                    [
                        'name' => 'related_record_id',
                        'type' => 'select',
                        'datatable' => 'test_related_records,label',
                        'parent_select' => 'parent_id,category_id',
                        'datatable_where' => 'active = 1',
                    ],
                    [
                        'name' => 'related_record_modal_id',
                        'type' => 'datamodal',
                        'datamodal_table' => 'test_related_records',
                        'datamodal_columns' => 'label,category_id',
                        'datamodal_where' => 'active = 1',
                        'datamodal_paginate' => 10,
                        'datamodal_select_to' => 'category_id:category_field',
                        'datamodal_columns_alias' => 'Label,Category',
                    ],
                ],
            ],
        ];
    }
}
