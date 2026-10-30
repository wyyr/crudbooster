<?php

namespace Tests\Fixtures;

class DynamicController
{
    public function getIndex(): string
    {
        return 'ok';
    }

    public function getExportReport(): string
    {
        return 'export';
    }

    public function postSaveRecord(): string
    {
        return 'saved';
    }
}
