<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Controller\Upload;

use App\Contract\UploadInterface;
use App\Controller\Upload\HeadUploadController;
use App\Factory\Type\Response\NoContentResponseFactory;
use App\Service\UploadAccessService;
use App\Type\Response\NoContentResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

#[Small]
#[CoversClass(HeadUploadController::class)]
class HeadUploadControllerTest extends TestCase
{
    use ProphecyTrait;

    private const string UPLOAD_ID = '02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub';

    public function testHeadReportsStateOfAuthorizedUpload(): void
    {
        $upload = $this->prophesize(UploadInterface::class)->reveal();
        $response = new NoContentResponse();

        $uploadAccessService = $this->prophesize(UploadAccessService::class);
        $uploadAccessService->loadAuthorizedUpload(self::UPLOAD_ID)->willReturn($upload)->shouldBeCalledOnce();
        $noContentResponseFactory = $this->prophesize(NoContentResponseFactory::class);
        $noContentResponseFactory->createNoContentResponseWithResumableUploadHeadersFromUpload($upload)->willReturn($response);

        $controller = new HeadUploadController($uploadAccessService->reveal(), $noContentResponseFactory->reveal());

        $this->assertSame($response, $controller->headUpload(self::UPLOAD_ID));
    }
}
