<?php

declare(strict_types=1);

namespace App\Controller\File;

use App\Helper\Regex;
use App\Service\UploadCreationService;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @SuppressWarnings("PHPMD.UnusedFormalParameter")
 */
class PutElementFileController extends AbstractController
{
    public function __construct(
        private UploadCreationService $uploadCreationService,
    ) {
    }

    #[Route(
        '/{id}/file',
        name: 'put-element-file',
        requirements: [
            'id' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['PUT']
    )]
    public function putElementFile(string $id, Request $request): Response
    {
        $elementId = UuidV4::fromString($id);

        return $this->uploadCreationService->handleUploadCreationFromRequest($elementId, $request);
    }
}
