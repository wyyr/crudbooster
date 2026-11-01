<?php

namespace crocodicstudio\crudbooster\uploads;

use crocodicstudio\crudbooster\helpers\CRUDBooster;
use Illuminate\Filesystem\FilesystemManager;
use RuntimeException;

class UploadWorkflow
{
    public function __construct(private FilesystemManager $filesystem) {}

    public function upload(string $name, bool $encrypt = false, mixed $id = null): ?string
    {
        if (! request()->hasFile($name)) {
            return null;
        }

        $userId = CRUDBooster::myId() ?: 0;
        if ($id) {
            $userId = $id;
        }

        $file = request()->file($name);
        $extension = $file->getClientOriginalExtension();
        $filename = str_slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $filePath = config('crudbooster.prefix_upload').'/uploads/'.$userId.'/'.date('Y-m');

        $disk = $this->filesystem->disk(config('crudbooster.filesystem_driver'));
        if (! $disk->makeDirectory($filePath)) {
            throw new RuntimeException('Unable to create the upload directory on the configured filesystem disk.');
        }

        if ($encrypt) {
            $filename = md5(str_random(5)).'.'.$extension;
        } else {
            $filename = str_slug($filename, '_').'.'.$extension;
        }

        if (! $disk->putFileAs($filePath, $file, $filename)) {
            throw new RuntimeException('Unable to store the uploaded file on the configured filesystem disk.');
        }

        return $filePath.'/'.$filename;
    }

    public function deleteIfExists(string $path): void
    {
        $disk = $this->filesystem->disk(config('crudbooster.filesystem_driver'));
        if ($disk->exists($path) && ! $disk->delete($path)) {
            throw new RuntimeException('Unable to delete the file from the configured filesystem disk.');
        }
    }
}
