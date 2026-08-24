<?php

declare(strict_types=1);

namespace App\Controller\File;

use App\Attribute\EndpointSupportsEtag;
use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Type\S3\FileOperationFactory;
use App\Helper\Regex;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\DigestService;
use App\Service\ElementManager;
use App\Service\ElementService;
use App\Service\FileRangeService;
use App\Service\FileService;
use App\Service\S3Service;
use App\Type\AccessType;
use App\Type\EtagType;
use App\Type\Response\BinaryStreamResponse;
use ArrayAccess;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class GetElementFileController extends AbstractController
{
    public function __construct(
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
        private ElementManager $elementManager,
        private ElementService $elementService,
        private FileService $fileService,
        private FileOperationFactory $fileOperationFactory,
        private S3Service $s3Service,
        private FileRangeService $fileRangeService,
        private DigestService $digestService,
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
    #[EndpointSupportsEtag(EtagType::FILE)]
    public function getElementFile(string $id, Request $request): BinaryStreamResponse
    {
        $elementId = UuidV4::fromString($id);
        $userId = $this->authProvider->getUserId();

        if (!$this->accessChecker->hasAccessToElement($userId, $elementId, AccessType::READ)) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $element = $this->elementManager->getElementOrFail($elementId);

        $fileName = $this->elementService->getFileName($element);
        $fileNameFallback = $this->fileService->getAsciiSafeFileName($fileName);

        $fileOperation = $this->fileOperationFactory->createFileOperationFromElement($element);

        $doesFileExist = $this->s3Service->existsFile($fileOperation);
        if (false === $doesFileExist) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $reprDigestHeaderValue = $this->getReprDigestHeaderValue($element);
        $contentType = $this->getStoredContentType($element);

        $rangeHeader = $request->headers->get('Range');
        if (null !== $rangeHeader) {
            $totalContentLength = $this->s3Service->getContentLength($fileOperation);
            $range = $this->fileRangeService->parseRangeHeader($rangeHeader, $totalContentLength);
            $object = $this->s3Service->getFileByteRange($fileOperation, $range->getStart(), $range->getEnd());

            return new BinaryStreamResponse($object, $fileName, $fileNameFallback, $range, $reprDigestHeaderValue, $contentType);
        }

        $object = $this->s3Service->getFile($fileOperation);

        return new BinaryStreamResponse($object, $fileName, $fileNameFallback, reprDigestHeaderValue: $reprDigestHeaderValue, contentType: $contentType);
    }

    private function getStoredContentType(NodeElementInterface|RelationElementInterface $element): ?string
    {
        if (!$element->hasProperty('file')) {
            return null;
        }
        $fileProperty = $element->getProperty('file');
        if (!is_array($fileProperty) && !($fileProperty instanceof ArrayAccess)) {
            return null;
        }
        // older records (predating the 'mimeType' rename) use 'mimetype' instead
        $mimeType = $fileProperty['mimeType'] ?? $fileProperty['mimetype'] ?? null;
        if (!is_string($mimeType) || '' === $mimeType) {
            return null;
        }

        return $mimeType;
    }

    private function getReprDigestHeaderValue(NodeElementInterface|RelationElementInterface $element): ?string
    {
        if (!$element->hasProperty('file')) {
            return null;
        }
        $fileProperty = $element->getProperty('file');
        // 'file' is read back from MongoDB; nested values (like 'hash') may still be BSONDocument/ArrayAccess
        // instances rather than plain arrays at this point, so array-offset access is used instead of is_array().
        if (!is_array($fileProperty) && !($fileProperty instanceof ArrayAccess)) {
            return null;
        }
        $hash = $fileProperty['hash'] ?? null;
        if (!is_array($hash) && !($hash instanceof ArrayAccess)) {
            return null;
        }
        $sha256 = $hash['sha256'] ?? null;
        if (!is_string($sha256) || 64 !== strlen($sha256)) {
            return null;
        }

        return $this->digestService->formatDigestHeaderValue($sha256);
    }
}
