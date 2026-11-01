<?php

namespace crocodicstudio\crudbooster\controllers;

use crocodicstudio\crudbooster\uploads\PreviewUploadedFileAction;

class FileController extends Controller
{
    public function getPreview($one, $two = null, $three = null, $four = null, $five = null)
    {
        return app(PreviewUploadedFileAction::class)->execute($one, $two, $three, $four, $five);
    }
}
