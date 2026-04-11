<?php

declare(strict_types=1);

namespace App\Controller\Upload;

use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Response\NoContentResponseFactory;
use App\Factory\Type\UploadFactory;
use App\Helper\Regex;
use App\Security\AuthProvider;
use App\Service\ElementManager;
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
        private UploadFactory $uploadFactory,
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
        $uploadElement = $this->elementManager->getElementOrFail(UuidV4::fromString($id));
        try {
            $upload = $this->uploadFactory->createUploadFromElement($uploadElement);
        } catch (Exception $e) {
            throw $e;
            //throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        if ($upload->getUploadOwner() !== $this->authProvider->getUserId()) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        return $this->noContentResponseFactory->createNoContentResponseWithResumableUploadHeadersFromUpload($upload);
    }
}
