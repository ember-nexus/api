<?php

declare(strict_types=1);

namespace App\Factory;

use AsyncAws\Core\Configuration;
use AsyncAws\S3\S3Client;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;

/**
 * @codeCoverageIgnore
 */
class S3ClientFactory
{
    public function __construct(
        private string $s3Endpoint,
        private string $s3AccessKeyId,
        private string $s3SecretAccessKey,
        private LoggerInterface $logger,
        private EmberNexusConfiguration $emberNexusConfiguration,
    ) {
    }

    public function createS3Client(): S3Client
    {
        $configuration = Configuration::create([
            Configuration::OPTION_ENDPOINT => $this->s3Endpoint,
            Configuration::OPTION_PATH_STYLE_ENDPOINT => 'true',
            Configuration::OPTION_ACCESS_KEY_ID => $this->s3AccessKeyId,
            Configuration::OPTION_SECRET_ACCESS_KEY => $this->s3SecretAccessKey,
        ]);

        // Symfony's HttpClient defaults to 6 concurrent connections per host, which throttles otherwise-parallel S3
        // operations, e.g. the concurrent `uploadPartCopy`/delete calls issued by S3Service while merging a large
        // file's chunks.
        $httpClient = HttpClient::create(maxHostConnections: $this->emberNexusConfiguration->getFileS3MaxHostConnections());

        return new S3Client($configuration, httpClient: $httpClient, logger: $this->logger);
    }
}
