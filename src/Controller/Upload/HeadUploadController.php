<?php

declare(strict_types=1);

namespace App\Controller\Upload;

use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Response\NoContentResponseFactory;
use App\Helper\Regex;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Type\UploadElement;
use Exception;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HeadUploadController extends AbstractController
{
    public function __construct(
        private AuthProvider $authProvider,
        private ElementManager $elementManager,
        private NoContentResponseFactory $noContentResponseFactory,
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

        $element = $this->elementManager->getElementOrFail($elementId);

        try {
            $uploadElement = UploadElement::createFromElement($element);
        } catch (Exception $e) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        if (null === $uploadElement->getUploadOwner()) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        if ($uploadElement->getUploadOwner()->toString() !== $userId->toString()) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        return $this->noContentResponseFactory->createNoContentResponseWithResumableUploadHeaders($uploadElement);
    }
}
