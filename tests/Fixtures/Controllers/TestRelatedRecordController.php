<?php

namespace App\Http\Controllers\CRUDBooster;

use crocodicstudio\crudbooster\controllers\CBController;

class TestRelatedRecordController extends CBController
{
    public function cbInit(): void
    {
        $this->table = 'test_related_records';
        $this->title_field = 'label';
    }
}
