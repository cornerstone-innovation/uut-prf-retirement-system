<?php

namespace App\Application\Services\Document;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Application\Services\Security\EncryptedFileService;

class InvestorDocumentStorageService
{
    public function __construct(
        private readonly EncryptedFileService $encryptedFileService
    ) {
    }

    public function storeEncrypted(
        UploadedFile $file,
        int $investorId,
        string $documentCode,
        string $disk = 's3'
    ): array {
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $storedFilename = Str::uuid()->toString() . '.enc';

        $path = "investors/{$investorId}/documents/{$documentCode}/{$storedFilename}";

        $rawContents = file_get_contents($file->getRealPath());
        $encryptedContents = $this->encryptedFileService->encrypt($rawContents);

        Storage::disk($disk)->put($path, $encryptedContents);

        return [
            'storage_disk' => $disk,
            'storage_path' => $path,
            'stored_filename' => $storedFilename,
            'original_filename' => $originalName,
            'file_extension' => $extension,
            'mime_type' => $file->getClientMimeType(),
            'file_size_bytes' => $file->getSize(),
        ];
    }

    public function getDecryptedContents(string $disk, string $path): string
    {
        $encryptedContents = Storage::disk($disk)->get($path);

        return $this->encryptedFileService->decrypt($encryptedContents);
    }
}