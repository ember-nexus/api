<?php

declare(strict_types=1);

namespace App\Factory\Response;

use App\Response\NoContentResponse;
use App\Type\UploadElement;
use EmberNexusBundle\Service\EmberNexusConfiguration;

class NoContentResponseFactory
{
    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
    ) {
    }

    public function createNoContentResponseWithResumableUploadHeaders(UploadElement $uploadElement): NoContentResponse
    {
        $response = new NoContentResponse();
        $headers = $response->headers;

        $headers->set('Upload-Complete', sprintf('?%s', $uploadElement->isUploadComplete() ? '1' : '0'));
        $headers->set('Upload-Offset', sprintf('%d', $uploadElement->getUploadOffset()));

        $uploadLength = $uploadElement->getUploadLength();
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
