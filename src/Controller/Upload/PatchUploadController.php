<?php

declare(strict_types=1);

namespace App\Controller\Upload;

use App\Factory\Exception\Client409ConflictExceptionFactory;
use App\Factory\Type\Request\PartialUploadRequestFactory;
use App\Factory\Type\Response\NoContentResponseFactory;
use App\Helper\Regex;
use App\Service\UploadAccessService;
use App\Service\UploadAppendService;
use App\Service\UploadLockService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PatchUploadController extends AbstractController
{
    public function __construct(
        private UploadAccessService $uploadAccessService,
        private UploadAppendService $uploadAppendService,
        private UploadLockService $uploadLockService,
        private PartialUploadRequestFactory $partialUploadRequestFactory,
        private NoContentResponseFactory $noContentResponseFactory,
        private Client409ConflictExceptionFactory $client409ConflictExceptionFactory,
    ) {
    }

    #[Route(
        '/upload/{id}',
        name: 'patch-upload',
        requirements: [
            'id' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['PATCH']
    )]
    public function patchUpload(string $id, Request $request): Response
    {
        // cheap checks first, so that only the owner of an upload can block it with the lock
        $upload = $this->uploadAccessService->loadAuthorizedUpload($id);

        // draft-ietf-httpbis-resumable-upload: concurrent appends must not corrupt the upload
        $lockToken = $this->uploadLockService->acquire($upload->getId());
        if (null === $lockToken) {
            throw $this->client409ConflictExceptionFactory->createFromDetail('Another request is currently modifying this upload, please retry once it has finished.');
        }

        try {
            // state may have changed while waiting for the lock, so the offset check needs fresh data
            $upload = $this->uploadAccessService->loadAuthorizedUpload($id);
            $upload = $this->uploadAppendService->append(
                $upload,
                $this->partialUploadRequestFactory->createPartialUploadRequestFromRequest($request),
                $request->headers->get('Repr-Digest')
            );

            return $this->noContentResponseFactory->createNoContentResponseWithResumableUploadHeadersFromUpload($upload);
        } finally {
            $this->uploadLockService->release($upload->getId(), $lockToken);
        }
    }
}
