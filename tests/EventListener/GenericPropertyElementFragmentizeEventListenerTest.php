<?php

declare(strict_types=1);

namespace App\Tests\EventListener\Neo4j;

use App\Event\NodeElementFragmentizeEvent;
use App\EventListener\GenericPropertyElementFragmentizeEventListener;
use App\Type\NodeElement;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Rfc4122\UuidV4;

class GenericPropertyElementFragmentizeEventListenerTest extends TestCase
{
    public function testNodeElementToRaw(): void
    {
        $element = (new NodeElement())
            ->setLabel('Node')
            ->setIdentifier(UuidV4::fromString('78f61245-8cfd-4039-ac7e-61407fa7e969'))
            ->addProperty('shortString', 'short string')
            ->addProperty('longString',
                'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut '.
                'labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores '.
                'et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem '.
                'ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et '.
                'dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. '.
                'Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit '.
                'amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna '.
                'aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita '.
                "kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.\n".
                'Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum '.
                'dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto odio dignissim qui blandit '.
                'praesent luptatu'
            )
            ->addProperty('integer', 1234)
            ->addProperty('float', 1.234)
            ->addProperty('array', [1, 2, 3, 4]);

        $event = new NodeElementFragmentizeEvent($element);
        $listener = new GenericPropertyElementFragmentizeEventListener();
        $listener->onNodeElementFragmentizeEvent($event);
        $cypherFragment = $event->getCypherFragment();
        $mongoFragment = $event->getMongoFragment();
        $elasticFragment = $event->getElasticFragment();

        $this->assertCount(3, $cypherFragment->getProperties());
        $this->assertSame('short string', $cypherFragment->getProperty('shortString'));
        $this->assertSame(1234, $cypherFragment->getProperty('integer'));
        $this->assertSame(1.234, $cypherFragment->getProperty('float'));
        $this->assertCount(2, $mongoFragment->getProperties());
        $this->assertStringStartsWith('Lorem', $mongoFragment->getProperty('longString'));
        $this->assertIsArray($mongoFragment->getProperty('array'));
        $this->assertCount(5, $elasticFragment->getProperties());
        $this->assertSame('short string', $elasticFragment->getProperty('shortString'));
        $this->assertSame(1234, $elasticFragment->getProperty('integer'));
        $this->assertSame(1.234, $elasticFragment->getProperty('float'));
        $this->assertStringStartsWith('Lorem', $elasticFragment->getProperty('longString'));
        $this->assertIsArray($elasticFragment->getProperty('array'));
    }
}
