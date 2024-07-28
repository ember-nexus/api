<?php

declare(strict_types=1);

namespace App\Controller\File;

use App\Helper\Regex;
use App\Response\JsonResponse;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GetTusUploadController extends AbstractController
{
    public function __construct(
    ) {
    }

    #[Route(
        '/tus-upload/{id}',
        name: 'get-tus-upload',
        requirements: [
            'id' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['GET']
    )]
    public function getTusUpload(string $id): Response
    {
        $uploadId = UuidV4::fromString($id);

        $response = new JsonResponse(
            [
                'upload-id' => $uploadId,
            ],
            Response::HTTP_OK,
            [
                'Location' => sprintf('/tus-upload/%s', $uploadId),
            ]
        );

        return $response;
    }
}
