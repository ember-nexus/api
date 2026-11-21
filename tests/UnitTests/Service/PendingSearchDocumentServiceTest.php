<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Service\PendingSearchDocumentService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Syndesi\ElasticDataStructures\Type\Document;

#[Small]
#[CoversClass(PendingSearchDocumentService::class)]
class PendingSearchDocumentServiceTest extends TestCase
{
    public function testHeldDocumentCanBeFetchedByIdentifier(): void
    {
        $service = new PendingSearchDocumentService();
        $document = new Document();
        $service->hold('a', $document);

        $this->assertSame($document, $service->get('a'));
        $this->assertNull($service->get('b'));
    }

    public function testReleaseAllReturnsEverythingAndEmptiesTheService(): void
    {
        $service = new PendingSearchDocumentService();
        $first = new Document();
        $second = new Document();
        $service->hold('a', $first);
        $service->hold('b', $second);

        $this->assertSame(['a' => $first, 'b' => $second], $service->releaseAll());
        $this->assertNull($service->get('a'));
        $this->assertSame([], $service->releaseAll());
    }

    public function testResetDropsHeldDocuments(): void
    {
        $service = new PendingSearchDocumentService();
        $service->hold('a', new Document());
        $service->reset();

        $this->assertNull($service->get('a'));
    }
}
