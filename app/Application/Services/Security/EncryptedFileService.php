<?php

namespace App\Application\Services\Security;

class EncryptedFileService
{
    public function encrypt(string $contents): string
    {
        return encrypt($contents);
    }

    public function decrypt(string $encryptedContents): string
    {
        return decrypt($encryptedContents);
    }
}