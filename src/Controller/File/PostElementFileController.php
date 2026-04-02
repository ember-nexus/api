<?php

declare(strict_types=1);

namespace App\Controller\File;

use App\Factory\Exception\Client409ConflictExceptionFactory;
use App\Helper\Regex;
use App\Service\ElementManager;
use App\Service\UploadCreationService;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @todo add support for etags
 * @SuppressWarnings("PHPMD.UnusedFormalParameter")
 */
class PostElementFileController extends AbstractController
{
    public function __construct(
        private UploadCreationService $uploadCreationService,
        private ElementManager $elementManager,
        private Client409ConflictExceptionFactory $client409ConflictExceptionFactory,
    ) {
    }

    #[Route(
        '/{id}/file',
        name: 'post-element-file',
        requirements: [
            'id' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['POST']
    )]
    public function postElementFile(string $id, Request $request): Response
    {
        $elementId = UuidV4::fromString($id);

        $element = $this->elementManager->getElementOrFail($elementId);
        $properties = $element->getProperties();
        if (array_key_exists('file', $properties)) {
            throw $this->client409ConflictExceptionFactory->createFromDetail(sprintf("Element with id '%s' already has an associated file; can not create new file. Delete existing file first or replace it with PUT.", $element->getId()?->toString() ?? 'missing element id'));
        }

        return $this->uploadCreationService->handleUploadCreationFromRequest($elementId, $request);
    }
}
