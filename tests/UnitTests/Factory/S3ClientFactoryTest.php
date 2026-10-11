<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Factory;

use App\Factory\S3ClientFactory;
use AsyncAws\Core\AbstractApi;
use AsyncAws\S3\S3Client;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionClass;
use Symfony\Component\HttpClient\CurlHttpClient;

#[Small]
#[CoversClass(S3ClientFactory::class)]
class S3ClientFactoryTest extends TestCase
{
    private function buildFactory(int $maxHostConnections = 16): S3ClientFactory
    {
        return new S3ClientFactory(
            'http://s3.example.test:9000',
            'access-key',
            'secret-key',
            new NullLogger(),
            (new EmberNexusConfiguration())->setFileS3MaxHostConnections($maxHostConnections),
        );
    }

    public function testCreateS3ClientReturnsClient(): void
    {
        $this->assertInstanceOf(S3Client::class, $this->buildFactory()->createS3Client());
    }

    /**
     * The configured value only takes effect deep inside the constructed AsyncAws/Symfony client graph, so
     * reflection is used to assert it actually arrived where it needs to.
     */
    private function readMaxHostConnections(S3Client $s3Client): int
    {
        $httpClientProperty = (new ReflectionClass(AbstractApi::class))->getProperty('httpClient');
        $httpClient = $httpClientProperty->getValue($s3Client);
        $this->assertInstanceOf(CurlHttpClient::class, $httpClient);

        $multiProperty = (new ReflectionClass(CurlHttpClient::class))->getProperty('multi');
        $multi = $multiProperty->getValue($httpClient);

        $maxHostConnectionsProperty = (new ReflectionClass($multi))->getProperty('maxHostConnections');

        return $maxHostConnectionsProperty->getValue($multi);
    }

    public function testCreateS3ClientUsesConfiguredMaxHostConnections(): void
    {
        $s3Client = $this->buildFactory(4)->createS3Client();

        $this->assertSame(4, $this->readMaxHostConnections($s3Client));
    }

    public function testCreateS3ClientUsesDifferentConfiguredMaxHostConnections(): void
    {
        $s3Client = $this->buildFactory(32)->createS3Client();

        $this->assertSame(32, $this->readMaxHostConnections($s3Client));
    }
}
