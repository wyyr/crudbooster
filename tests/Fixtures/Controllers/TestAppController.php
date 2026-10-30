<?php

namespace App\Http\Controllers\CRUDBooster;

class TestAppController
{
    public function getIndex(): string
    {
        return 'ok';
    }

    public function getExportReport(): string
    {
        return 'export';
    }
}
