<?php

namespace crocodicstudio\crudbooster\uploads;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class FilePreviewWorkflow
{
    public function preview(string $one, ?string $two = null, ?string $three = null, ?string $four = null, ?string $five = null): BaseResponse
    {
        $segments = array_values(array_filter(
            [$one, $two, $three, $four, $five],
            static fn (?string $segment): bool => $segment !== null && $segment !== '',
        ));

        foreach ($segments as $segment) {
            if ($segment === '.' || $segment === '..' || str_contains($segment, '/') || str_contains($segment, '\\') || str_contains($segment, "\0")) {
                abort(404);
            }
        }

        $filename = $segments[count($segments) - 1];
        $filePath = 'uploads/'.implode('/', $segments);
        $diskName = config('crudbooster.filesystem_driver');
        $disk = Storage::disk($diskName);
        if (! $disk->exists($filePath)) {
            abort(404);
        }

        $fileTime = $disk->lastModified($filePath);
        $fileSize = $disk->size($filePath);
        $contentType = $disk->mimeType($filePath) ?: 'application/octet-stream';
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $imageExtensions = explode(',', config('crudbooster.IMAGE_EXTENSIONS', 'jpg,png,gif,bmp'));
        $imageFileSize = 0;

        if (in_array($extension, $imageExtensions, true)) {
            $defaultThumbnail = config('crudbooster.DEFAULT_THUMBNAIL_WIDTH');
            if ($defaultThumbnail != 0) {
                $width = Request::get('w') ?: $defaultThumbnail;
                $height = Request::get('h') ?: $width;
            } else {
                $width = Request::get('w');
                $height = Request::get('h') ?: $width;
            }

            $cacheKey = 'cb_img_'.md5(implode('|', [
                (string) $diskName,
                $filePath,
                (string) $fileTime,
                (string) $fileSize,
                (string) $width,
                (string) $height,
            ]));
            $imageRaw = Cache::remember($cacheKey, 86400, function () use ($disk, $filePath, $width, $height, $extension) {
                $imageContent = $disk->get($filePath);
                if ($width) {
                    $manager = ImageManager::gd();
                    $image = $manager->read($imageContent);
                    $image->cover($width, $height ?: $width);

                    return (string) $image->encodeByExtension($extension);
                }

                return $imageContent;
            });

            $imageFileSize = mb_strlen($imageRaw, '8bit') ?: 0;
        }

        $lastModified = gmdate('r', $fileTime);
        $headers = [
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Last-Modified' => $lastModified,
            'Cache-Control' => 'must-revalidate',
            'Expires' => gmdate('r', $fileTime + 31556926),
            'Pragma' => 'public',
            'Etag' => md5($fileTime.$filePath),
            'Content-Type' => $contentType,
            'Content-Length' => $imageFileSize ?: $fileSize,
        ];

        $isNotModified = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $lastModified;
        $isSameEtag = isset($_SERVER['HTTP_IF_NONE_MATCH'])
            && str_replace('"', '', stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) == $headers['Etag'];

        if (in_array($extension, $imageExtensions, true)) {
            if ($isNotModified || $isSameEtag) {
                return Response::make('', 304, $headers);
            }

            return Response::make($imageRaw, 200, $headers);
        }

        $stream = $disk->readStream($filePath);
        if (! is_resource($stream)) {
            throw new RuntimeException('Unable to read the file from the configured filesystem disk.');
        }

        $sendStream = static function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        };

        if (Request::get('download')) {
            return Response::streamDownload($sendStream, $filename, $headers);
        }

        return Response::stream($sendStream, 200, $headers);
    }
}
