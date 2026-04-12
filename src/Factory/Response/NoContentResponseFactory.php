<?php

declare(strict_types=1);

namespace App\Factory\Response;

use App\Response\NoContentResponse;
use App\Type\Upload;
use DateTimeZone;
use EmberNexusBundle\Service\EmberNexusConfiguration;

class NoContentResponseFactory
{
    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
    ) {
    }

    public function createNoContentResponseWithLocationHeader(string $location): NoContentResponse
    {
        $response = new NoContentResponse();
        $headers = $response->headers;

        $headers->set('Location', $location);

        return $response;
    }

    public function createNoContentResponseWithResumableUploadHeadersFromUpload(Upload $upload, ?string $location = null): NoContentResponse
    {
        $response = new NoContentResponse();
        $headers = $response->headers;

        $headers->set('Upload-Complete', sprintf('?%s', $upload->isUploadComplete() ? '1' : '0'));
        $headers->set('Upload-Offset', sprintf('%d', $upload->getUploadOffset()));

        if (null !== $location) {
            $headers->set('Location', $location);
        }

        $uploadLength = $upload->getUploadLength();
        if (null !== $uploadLength) {
            $headers->set('Upload-Length', sprintf('%d', $uploadLength));
        }

        $headers->set(
            'Upload-Limit',
            sprintf(
                'max-age=%d, max-size=%d, min-append-size=%d, max-append-size=%d',
                $this->emberNexusConfiguration->getFileUploadExpiresInSecondsAfterFirstRequest(),
                $this->emberNexusConfiguration->getFileMaxFileSizeInBytes(),
                $this->emberNexusConfiguration->getFileUploadMinChunkSizeInBytes(),
                $this->emberNexusConfiguration->getFileUploadMaxChunkSizeInBytes(),
            )
        );
        $headers->set('Expires', $upload->getExpires()->setTimezone(new DateTimeZone('UTC'))->format('D, d M Y H:i:s \\G\\M\\T'));
        $headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
