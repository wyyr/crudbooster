<?php

namespace crocodicstudio\crudbooster\uploads;

use Illuminate\Filesystem\FilesystemManager;
use RuntimeException;

class DeleteUploadedFileAction
{
    public function __construct(private FilesystemManager $filesystem) {}

    public function execute(string $path): void
    {
        $disk = $this->filesystem->disk(config('crudbooster.filesystem_driver'));
        if ($disk->exists($path) && ! $disk->delete($path)) {
            throw new RuntimeException('Unable to delete the file from the configured filesystem disk.');
        }
    }
}
