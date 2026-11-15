<?php

declare(strict_types=1);

namespace App\Controller\Upload;

use App\Factory\Type\Response\NoContentResponseFactory;
use App\Helper\Regex;
use App\Service\UploadAccessService;
use App\Type\Response\NoContentResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class HeadUploadController extends AbstractController
{
    public function __construct(
        private UploadAccessService $uploadAccessService,
        private NoContentResponseFactory $noContentResponseFactory,
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
    public function headUpload(string $id): NoContentResponse
    {
        return $this->noContentResponseFactory->createNoContentResponseWithResumableUploadHeadersFromUpload(
            $this->uploadAccessService->loadAuthorizedUpload($id)
        );
    }
}
