<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Contract\S3\S3TechnicalLimitsInterface;
use App\Service\S3TechnicalLimitsValidator;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
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
    ): S3TechnicalLimitsValidator {
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileUploadMinChunkSizeInBytes()->willReturn($configuredMinChunkSize);
        $emberNexusConfiguration->getFileUploadChunkDigitsLength()->willReturn($configuredChunkDigitsLength);
        $emberNexusConfiguration->getFileMaxFileSizeInBytes()->willReturn($configuredMaxFileSize);

        $s3TechnicalLimits = $this->prophesize(S3TechnicalLimitsInterface::class);
        $s3TechnicalLimits->getMinChunkSizeInBytes()->willReturn($technicalMinChunkSize);
        $s3TechnicalLimits->getMaxChunkCount()->willReturn($technicalMaxChunkCount);
        $s3TechnicalLimits->getMaxObjectSizeInBytes()->willReturn($technicalMaxObjectSize);

        return new S3TechnicalLimitsValidator($emberNexusConfiguration->reveal(), $s3TechnicalLimits->reveal());
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
        $validator = $this->buildValidator(
            configuredMinChunkSize: 1024 * 1024,
            configuredChunkDigitsLength: 4,
            configuredMaxFileSize: 10 * 1024 * 1024 * 1024,
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/uploadMinChunkSizeInBytes/');
        $validator->validate();
    }

    public function testThrowsIfConfiguredChunkDigitsLengthAllowsMoreChunksThanTechnicallySupported(): void
    {
        $validator = $this->buildValidator(
            configuredMinChunkSize: 5 * 1024 * 1024,
            configuredChunkDigitsLength: 5, // allows up to 100,000 chunks, exceeds the 10,000 technical limit
            configuredMaxFileSize: 10 * 1024 * 1024 * 1024,
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/uploadChunkDigitsLength/');
        $validator->validate();
    }

    public function testThrowsIfConfiguredMaxFileSizeExceedsTechnicalMaxObjectSize(): void
    {
        $validator = $this->buildValidator(
            configuredMinChunkSize: 5 * 1024 * 1024,
            configuredChunkDigitsLength: 4,
            configuredMaxFileSize: 6 * 1024 * 1024 * 1024 * 1024,
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/maxFileSizeInBytes/');
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
