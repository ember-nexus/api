<?php

declare(strict_types=1);

namespace App\Contract\S3;

interface FileOperationInterface
{
    public function getBucket(): string;

    public function getKey(): string;
}
