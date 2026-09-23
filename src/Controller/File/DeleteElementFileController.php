<?php

declare(strict_types=1);

namespace App\Controller\File;

use App\Attribute\EndpointSupportsEtag;
use App\EventSystem\ElementFileDelete\Event\ElementFileDeleteEvent;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Type\S3\S3OperationFactory;
use App\Helper\Regex;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\ElementFileDeletionService;
use App\Service\ElementManager;
use App\Service\ElementService;
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
        private ElementFileDeletionService $elementFileDeletionService,
        private ElementManager $elementManager,
        private ElementService $elementService,
        private EventDispatcherInterface $eventDispatcher,
        private S3OperationFactory $s3OperationFactory,
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
        if (!$this->elementService->hasFile($element)) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $deleteFileOperation = $this->s3OperationFactory->createFileOperationFromElement($element);

        // graph first: a failed flush leaves the file untouched, a failed S3 delete only leaves an orphaned object
        $element->addProperty('file', null);
        $element->addProperty('hasFile', false);
        $this->elementManager->merge($element);
        $this->elementManager->flush();

        $this->elementFileDeletionService->deleteFile($deleteFileOperation);

        $this->eventDispatcher->dispatch(new ElementFileDeleteEvent($elementId));

        return new NoContentResponse();
    }
}
