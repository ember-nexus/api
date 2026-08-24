<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Contract\S3\S3TechnicalLimitsInterface;
use App\Exception\Server500LogicErrorException;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Service\S3TechnicalLimitsValidator;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

#[Small]
#[CoversClass(S3TechnicalLimitsValidator::class)]
class S3TechnicalLimitsValidatorTest extends TestCase
{
    use ProphecyTrait;

    private function buildValidator(
        int $configuredMinChunkSize,
        int $configuredChunkDigitsLength,
        int $configuredMaxFileSize,
        int $technicalMinChunkSize = 5 * 1024 * 1024,
        int $technicalMaxChunkCount = 10_000,
        int $technicalMaxObjectSize = 5 * 1024 * 1024 * 1024 * 1024,
        ?Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory = null,
    ): S3TechnicalLimitsValidator {
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileUploadMinChunkSizeInBytes()->willReturn($configuredMinChunkSize);
        $emberNexusConfiguration->getFileUploadChunkDigitsLength()->willReturn($configuredChunkDigitsLength);
        $emberNexusConfiguration->getFileMaxFileSizeInBytes()->willReturn($configuredMaxFileSize);

        $s3TechnicalLimits = $this->prophesize(S3TechnicalLimitsInterface::class);
        $s3TechnicalLimits->getMinChunkSizeInBytes()->willReturn($technicalMinChunkSize);
        $s3TechnicalLimits->getMaxChunkCount()->willReturn($technicalMaxChunkCount);
        $s3TechnicalLimits->getMaxObjectSizeInBytes()->willReturn($technicalMaxObjectSize);

        return new S3TechnicalLimitsValidator(
            $emberNexusConfiguration->reveal(),
            $s3TechnicalLimits->reveal(),
            $server500LogicErrorExceptionFactory ?? $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );
    }

    public function testValidConfigurationPasses(): void
    {
        $validator = $this->buildValidator(
            configuredMinChunkSize: 5 * 1024 * 1024,
            configuredChunkDigitsLength: 4,
            configuredMaxFileSize: 10 * 1024 * 1024 * 1024,
        );

        $validator->validate();
        $this->addToAssertionCount(1);
    }

    public function testThrowsIfConfiguredMinChunkSizeIsBelowTechnicalMinimum(): void
    {
        $exception = $this->prophesize(Server500LogicErrorException::class)->reveal();
        $server500LogicErrorExceptionFactory = $this->prophesize(Server500LogicErrorExceptionFactory::class);
        $server500LogicErrorExceptionFactory->createFromTemplate(Argument::containingString('uploadMinChunkSizeInBytes'))
            ->shouldBeCalledOnce()
            ->willReturn($exception);

        $validator = $this->buildValidator(
            configuredMinChunkSize: 1024 * 1024,
            configuredChunkDigitsLength: 4,
            configuredMaxFileSize: 10 * 1024 * 1024 * 1024,
            server500LogicErrorExceptionFactory: $server500LogicErrorExceptionFactory->reveal(),
        );

        $this->expectException(Server500LogicErrorException::class);
        $validator->validate();
    }

    public function testThrowsIfConfiguredChunkDigitsLengthAllowsMoreChunksThanTechnicallySupported(): void
    {
        $exception = $this->prophesize(Server500LogicErrorException::class)->reveal();
        $server500LogicErrorExceptionFactory = $this->prophesize(Server500LogicErrorExceptionFactory::class);
        $server500LogicErrorExceptionFactory->createFromTemplate(Argument::containingString('uploadChunkDigitsLength'))
            ->shouldBeCalledOnce()
            ->willReturn($exception);

        $validator = $this->buildValidator(
            configuredMinChunkSize: 5 * 1024 * 1024,
            configuredChunkDigitsLength: 5, // allows up to 100,000 chunks, exceeds the 10,000 technical limit
            configuredMaxFileSize: 10 * 1024 * 1024 * 1024,
            server500LogicErrorExceptionFactory: $server500LogicErrorExceptionFactory->reveal(),
        );

        $this->expectException(Server500LogicErrorException::class);
        $validator->validate();
    }

    public function testThrowsIfConfiguredMaxFileSizeExceedsTechnicalMaxObjectSize(): void
    {
        $exception = $this->prophesize(Server500LogicErrorException::class)->reveal();
        $server500LogicErrorExceptionFactory = $this->prophesize(Server500LogicErrorExceptionFactory::class);
        $server500LogicErrorExceptionFactory->createFromTemplate(Argument::containingString('maxFileSizeInBytes'))
            ->shouldBeCalledOnce()
            ->willReturn($exception);

        $validator = $this->buildValidator(
            configuredMinChunkSize: 5 * 1024 * 1024,
            configuredChunkDigitsLength: 4,
            configuredMaxFileSize: 6 * 1024 * 1024 * 1024 * 1024,
            server500LogicErrorExceptionFactory: $server500LogicErrorExceptionFactory->reveal(),
        );

        $this->expectException(Server500LogicErrorException::class);
        $validator->validate();
    }

    public function testExactlyAtTechnicalLimitsIsAllowed(): void
    {
        $validator = $this->buildValidator(
            configuredMinChunkSize: 5 * 1024 * 1024,
            configuredChunkDigitsLength: 4, // 10,000 chunks, exactly the technical maximum
            configuredMaxFileSize: 5 * 1024 * 1024 * 1024 * 1024,
        );

        $validator->validate();
        $this->addToAssertionCount(1);
    }
}
