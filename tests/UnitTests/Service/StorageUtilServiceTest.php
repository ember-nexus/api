<?php

declare(strict_types=1);

namespace App\tests\UnitTests\Service;

use App\Exception\Server500LogicErrorException;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Service\StorageUtilService;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StorageUtilServiceTest extends TestCase
{
    private function getStorageUtilService(
        ?EmberNexusConfiguration $emberNexusConfiguration = null
    ): StorageUtilService {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('url');
        $server500Bag = $this->createMock(ParameterBagInterface::class);
        $server500Bag->method('get')->willReturn('dev');
        $server500LogicExceptionFactory = new Server500LogicExceptionFactory(
            $urlGenerator,
            $server500Bag,
            $this->createMock(LoggerInterface::class)
        );

        return new StorageUtilService(
            $emberNexusConfiguration ?? $this->createMock(EmberNexusConfiguration::class),
            $server500LogicExceptionFactory,
        );
    }

    public function testUuidToNestedFolderStructure(): void
    {
        $storageUtilService = $this->getStorageUtilService();

        $uuid = Uuid::fromString('3207d629-7199-4c2d-9b2a-b0fba10fe309');

        try {
            $storageUtilService->uuidToNestedFolderStructure($uuid, -1);
        } catch (Exception $e) {
            $this->assertInstanceOf(Server500LogicErrorException::class, $e);
            /**
             * @var $e Server500LogicErrorException
             */
            $this->assertSame('Unable to generate nested folder structure from uuid with negative level argument.', $e->getDetail());
        }

        try {
            $storageUtilService->uuidToNestedFolderStructure($uuid, 0, -1);
        } catch (Exception $e) {
            $this->assertInstanceOf(Server500LogicErrorException::class, $e);
            /**
             * @var $e Server500LogicErrorException
             */
            $this->assertSame('Unable to generate nested folder structure from uuid with level length less than 1.', $e->getDetail());
        }

        try {
            $storageUtilService->uuidToNestedFolderStructure($uuid, 0, 0);
        } catch (Exception $e) {
            $this->assertInstanceOf(Server500LogicErrorException::class, $e);
            /**
             * @var $e Server500LogicErrorException
             */
            $this->assertSame('Unable to generate nested folder structure from uuid with level length less than 1.', $e->getDetail());
        }

        try {
            $storageUtilService->uuidToNestedFolderStructure($uuid, 8, 9);
        } catch (Exception $e) {
            $this->assertInstanceOf(Server500LogicErrorException::class, $e);
            /**
             * @var $e Server500LogicErrorException
             */
            $this->assertSame('Unable to generate nested folder structure as long as product of levels and level length exceeds length of uuid without dashes.', $e->getDetail());
        }

        $generatedStructure = $storageUtilService->uuidToNestedFolderStructure($uuid);
        $this->assertSame('3207d629-7199-4c2d-9b2a-b0fba10fe309', $generatedStructure);

        $generatedStructure = $storageUtilService->uuidToNestedFolderStructure($uuid, 1, 1);
        $this->assertSame('3/3207d629-7199-4c2d-9b2a-b0fba10fe309', $generatedStructure);

        $generatedStructure = $storageUtilService->uuidToNestedFolderStructure($uuid, 1, 3);
        $this->assertSame('320/3207d629-7199-4c2d-9b2a-b0fba10fe309', $generatedStructure);

        $generatedStructure = $storageUtilService->uuidToNestedFolderStructure($uuid, 3, 1);
        $this->assertSame('3/2/0/3207d629-7199-4c2d-9b2a-b0fba10fe309', $generatedStructure);

        $generatedStructure = $storageUtilService->uuidToNestedFolderStructure($uuid, 2, 2);
        $this->assertSame('32/07/3207d629-7199-4c2d-9b2a-b0fba10fe309', $generatedStructure);

        $generatedStructure = $storageUtilService->uuidToNestedFolderStructure($uuid, 6, 5);
        $this->assertSame('3207d/62971/994c2/d9b2a/b0fba/10fe3/3207d629-7199-4c2d-9b2a-b0fba10fe309', $generatedStructure);

        $generatedStructure = $storageUtilService->uuidToNestedFolderStructure($uuid, 15, 2);
        $this->assertSame('32/07/d6/29/71/99/4c/2d/9b/2a/b0/fb/a1/0f/e3/3207d629-7199-4c2d-9b2a-b0fba10fe309', $generatedStructure);
    }
}
