<?php

declare(strict_types=1);

namespace App\Controller\File;

use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Helper\Regex;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\StorageUtilService;
use App\Type\AccessType;
use AsyncAws\S3\S3Client;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class GetElementFileController extends AbstractController
{
    public function __construct(
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
        private S3Client $s3Client,
        private EmberNexusConfiguration $emberNexusConfiguration,
        private StorageUtilService $storageUtilService,
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory,
    ) {
    }

    #[Route(
        '/{id}/file',
        name: 'get-element-file',
        requirements: [
            'id' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['GET']
    )]
    public function getElementFile(string $id, Request $request): Response
    {
        $id = UuidV4::fromString($id);
        $userId = $this->authProvider->getUserId();

        if (!$this->accessChecker->hasAccessToElement($userId, $id, AccessType::READ)) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $objectConfig = [
            'Bucket' => $this->emberNexusConfiguration->getFileS3StorageBucket(),
            'Key' => $this->storageUtilService->getStorageBucketKey($id),
        ];
        $status = $this->s3Client->objectExists($objectConfig);

        if (!$status->isSuccess()) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $object = $this->s3Client->getObject($objectConfig);

        $stream = $object->getBody()->getContentAsResource();

        $response = new StreamedResponse();
        $response->headers->set('Content-Length', (string) ($object->getContentLength() ?? 0));
        $response->headers->set('Content-Type', 'application/octet-stream');

        $response->setCallback(function () use ($stream): void {
            while (!feof($stream)) {
                $buffer = fread($stream, StorageUtilService::STREAM_CHUNK_SIZE);
                if (false === $buffer || 0 === strlen($buffer)) {
                    break;
                }
                echo $buffer;
                //                ob_flush();
                flush();
            }
            fclose($stream);
        });

        return $response;
    }
}
