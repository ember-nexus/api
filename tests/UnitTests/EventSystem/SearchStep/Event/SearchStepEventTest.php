<?php

declare(strict_types=1);

namespace App\tests\UnitTests\EventSystem\SearchStep\Event;

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
}
