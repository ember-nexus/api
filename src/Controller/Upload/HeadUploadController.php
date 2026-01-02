<?php

declare(strict_types=1);

namespace App\Controller\Upload;

use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Helper\Regex;
use App\Response\NoContentResponse;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Type\UploadElement;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HeadUploadController extends AbstractController
{
    public function __construct(
        private AuthProvider $authProvider,
        private EmberNexusConfiguration $emberNexusConfiguration,
        private ElementManager $elementManager,
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory,
    ) {
    }

    #[Route(
        '/upload/{id}',
        name: 'head-upload',
        requirements: [
            'id' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['HEAD']
    )]
    public function headUpload(string $id): Response
    {
        $elementId = UuidV4::fromString($id);
        $userId = $this->authProvider->getUserId();

        $element = $this->elementManager->getElement($elementId);
        if (null === $element) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        try {
            $uploadElement = UploadElement::createFromElement($element);
        } catch (Exception $e) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        if (null === $uploadElement->getUploadOwner()) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        if ($uploadElement->getUploadOwner() !== $userId) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        header(sprintf('Upload-Complete: ?%s', $uploadElement->isUploadComplete() ? '1' : '0'));
        header(sprintf('Upload-Offset: %d', $uploadElement->getUploadOffset()));
        $uploadLength = $uploadElement->getUploadLength();
        if (null !== $uploadLength) {
            header(sprintf('Upload-Length: %d', $uploadLength));
        }
        header(sprintf(
            'Upload-Limit: max-age=%d, max-size=%d, min-append-size=%d, max-append-size=%d',
            $this->emberNexusConfiguration->getFileUploadExpiresInSecondsAfterFirstRequest(),
            $this->emberNexusConfiguration->getFileMaxFileSizeInBytes(),
            $this->emberNexusConfiguration->getFileUploadMinChunkSizeInBytes(),
            $this->emberNexusConfiguration->getFileUploadMaxChunkSizeInBytes(),
        ));
        header('Cache-Control: no-store');

        return new NoContentResponse();
    }
}
