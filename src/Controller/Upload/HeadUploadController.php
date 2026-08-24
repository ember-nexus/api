<?php

declare(strict_types=1);

namespace App\Controller\Upload;

use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Type\Response\NoContentResponseFactory;
use App\Factory\Type\UploadFactory;
use App\Helper\Regex;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Type\AccessType;
use Exception;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HeadUploadController extends AbstractController
{
    public function __construct(
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
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
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $userId = $this->authProvider->getUserId();
        if ($upload->getUploadOwner()->toString() !== $userId->toString()) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }
        // an upload whose target element is gone (e.g. deleted mid-upload) or no longer accessible can never be
        // completed - report it the same way PATCH already would, instead of an upload that looks healthy
        if (!$this->accessChecker->hasAccessToElement($userId, $upload->getUploadTarget(), AccessType::UPDATE)) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        return $this->noContentResponseFactory->createNoContentResponseWithResumableUploadHeadersFromUpload($upload);
    }
}
