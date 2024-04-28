<?php

declare(strict_types=1);

namespace App\Factory;

use AsyncAws\Core\Configuration;
use AsyncAws\S3\S3Client;

class S3ClientFactory
{
    public function __construct(
        private string $s3Endpoint,
        private string $s3AccessKeyId,
        private string $s3SecretAccessKey
    ) {
    }

    public function createS3Client(): S3Client
    {
        $configuration = Configuration::create([
            Configuration::OPTION_ENDPOINT => $this->s3Endpoint,
            Configuration::OPTION_PATH_STYLE_ENDPOINT => true,
            Configuration::OPTION_ACCESS_KEY_ID => $this->s3AccessKeyId,
            Configuration::OPTION_SECRET_ACCESS_KEY => $this->s3SecretAccessKey,
        ]);

        return new S3Client($configuration);
    }
}
