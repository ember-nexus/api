<?php

declare(strict_types=1);

namespace App\Factory\Response;

use App\Response\NoContentResponse;
use App\Type\Upload;
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

    public function createNoContentResponseWithResumableUploadHeadersFromUpload(Upload $upload): NoContentResponse
    {
        $response = new NoContentResponse();
        $headers = $response->headers;

        $headers->set('Upload-Complete', sprintf('?%s', $upload->isUploadComplete() ? '1' : '0'));
        $headers->set('Upload-Offset', sprintf('%d', $upload->getUploadOffset()));

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
        $headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
