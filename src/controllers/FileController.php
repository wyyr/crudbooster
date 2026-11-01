<?php

namespace crocodicstudio\crudbooster\controllers;

use crocodicstudio\crudbooster\uploads\FilePreviewWorkflow;

class FileController extends Controller
{
    public function getPreview($one, $two = null, $three = null, $four = null, $five = null)
    {
        return app(FilePreviewWorkflow::class)->preview($one, $two, $three, $four, $five);
    }
}
