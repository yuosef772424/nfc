<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

trait FileUploadTrait
{
    /**
     * رفع ملف وتخزينه في القرص المحدد.
     * @return string مسار الملف المخزن
     */
    protected function uploadFile(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        return $file->store($directory, $disk);
    }

    /**
     * حذف ملف من التخزين.
     */
    protected function deleteFile(string $path, string $disk = 'public'): bool
    {
        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }
        return false;
    }

    /**
     * التحقق من أن الملف من نوع صورة وضمن الحجم المسموح.
     */
    protected function validateImage(UploadedFile $file, int $maxSizeKB = 2048, array $allowedMimes = ['jpg', 'jpeg', 'png']): void
    {
        if (!in_array($file->getClientOriginalExtension(), $allowedMimes)) {
            throw ValidationException::withMessages(['file' => 'Invalid image type.']);
        }
        if ($file->getSize() > $maxSizeKB * 1024) {
            throw ValidationException::withMessages(['file' => "Image size must be less than {$maxSizeKB} KB."]);
        }
    }
}