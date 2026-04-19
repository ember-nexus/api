<?php

declare(strict_types=1);

namespace App\Controller\File;

use App\Attribute\EndpointSupportsEtag;
use App\EventSystem\ElementFileDelete\Event\ElementFileDeleteEvent;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Type\S3\FileOperationFactory;
use App\Helper\Regex;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Service\S3Service;
use App\Type\AccessType;
use App\Type\EtagType;
use App\Type\Response\NoContentResponse;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings("PHPMD.UnusedFormalParameter")
 */
class DeleteElementFileController extends AbstractController
{
    public function __construct(
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
        private S3Service $s3Service,
        private ElementManager $elementManager,
        private EventDispatcherInterface $eventDispatcher,
        private FileOperationFactory $fileOperationFactory,
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory,
    ) {
    }

    #[Route(
        '/{id}/file',
        name: 'delete-element-file',
        requirements: [
            'id' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['DELETE']
    )]
    #[EndpointSupportsEtag(EtagType::FILE)]
    public function deleteElementFile(string $id): Response
    {
        $elementId = UuidV4::fromString($id);
        $userId = $this->authProvider->getUserId();

        if (!$this->accessChecker->hasAccessToElement($userId, $elementId, AccessType::UPDATE)) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $element = $this->elementManager->getElementOrFail($elementId);
        $deleteFileOperation = $this->fileOperationFactory->createFileOperationFromElement($element);
        $this->s3Service->deleteFile($deleteFileOperation);

        $element->removeProperty('file');
        $this->elementManager->merge($element);
        $this->elementManager->flush();

        $this->eventDispatcher->dispatch(new ElementFileDeleteEvent($elementId));

        return new NoContentResponse();
    }
}
