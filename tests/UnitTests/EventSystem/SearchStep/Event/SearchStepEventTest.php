<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\SearchStep\Event;

use App\EventSystem\SearchStep\Event\SearchStepEvent;
use App\Type\SearchStepType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(SearchStepEvent::class)]
class SearchStepEventTest extends TestCase
{
    public function testDebugInputDataIsSetDuringCreation(): void
    {
        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            'some query',
            ['key' => 'parameter']
        );
        $this->assertSame(SearchStepType::ELEMENT_HYDRATION, $event->getType());
        $this->assertSame('some query', $event->getQuery());
        $this->assertIsArray($event->getParameters());
        $this->assertSame('parameter', $event->getParameters()['key']);
        $debugData = $event->getDebugData();
        $this->assertIsArray($debugData);
        $this->assertArrayHasKey('input', $debugData);
        $this->assertSame('element-hydration', $debugData['input']['type']);
        $this->assertSame('some query', $debugData['input']['query']);
        $this->assertSame('parameter', $debugData['input']['parameters']['key']);
    }

    public function testResultMethods(): void
    {
        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            null,
            []
        );
        $this->assertCount(0, $event->getResults());
        $data = [
            'test' => 'value',
            'a' => 'b',
        ];
        $event->setResults($data);
        $this->assertCount(2, $event->getResults());
        $this->assertSame($data, $event->getResults());
    }

    public function testResolvedQueryIsNullUntilSet(): void
    {
        $event = new SearchStepEvent(SearchStepType::ELASTICSEARCH_QUERY_DSL_MIXIN, ['match_all' => []], []);

        $this->assertNull($event->getResolvedQuery());
        $this->assertSame($event, $event->setResolvedQuery(['match_all' => []]));
        $this->assertSame(['match_all' => []], $event->getResolvedQuery());
    }

    public function testElementMetadataDefaultsToEmpty(): void
    {
        $event = new SearchStepEvent(SearchStepType::ELEMENT_HYDRATION, null, []);

        $this->assertSame([], $event->getPreviousElementMetadata());
        $this->assertSame([], $event->getElementMetadata());
    }

    public function testPreviousElementMetadataIsProvidedByConstructorAndNotPartOfTheDebugInput(): void
    {
        $previous = ['02IiNr0G8viOVKjvj8DUNVx7k7SGySTlPgIzB' => [0 => ['score' => 1.5]]];

        $event = new SearchStepEvent(SearchStepType::ELEMENT_HYDRATION, null, [], $previous);

        $this->assertSame($previous, $event->getPreviousElementMetadata());
        $this->assertSame(['type', 'query', 'parameters'], array_keys($event->getDebugData()['input']));
    }

    public function testElementMetadataCanBeSet(): void
    {
        $event = new SearchStepEvent(SearchStepType::ELASTICSEARCH_QUERY_DSL_MIXIN, [], []);
        $metadata = ['02IiNr0G8viOVKjvj8DUNVx7k7SGySTlPgIzB' => ['score' => 1.5]];

        $this->assertSame($event, $event->setElementMetadata($metadata));

        $this->assertSame($metadata, $event->getElementMetadata());
        $this->assertSame([], $event->getPreviousElementMetadata());
    }
}
