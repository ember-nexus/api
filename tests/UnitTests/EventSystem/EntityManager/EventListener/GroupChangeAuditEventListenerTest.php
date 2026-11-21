<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\EntityManager\EventListener;

use App\EventSystem\EntityManager\Event\ElementPostDeleteEvent;
use App\EventSystem\EntityManager\Event\ElementPostMergeEvent;
use App\EventSystem\EntityManager\EventListener\GroupChangeAuditEventListener;
use App\Security\AuthProvider;
use App\Type\NodeElement;
use App\Type\RelationElement;
use App\Type\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

#[Small]
#[CoversClass(GroupChangeAuditEventListener::class)]
class GroupChangeAuditEventListenerTest extends TestCase
{
    use ProphecyTrait;

    private function buildListener(LoggerInterface $logger, bool $isAnonymous = false): GroupChangeAuditEventListener
    {
        $authProvider = $this->prophesize(AuthProvider::class);
        $authProvider->isAnonymous()->willReturn($isAnonymous);
        if (!$isAnonymous) {
            $authProvider->getUserId()->willReturn(Uuid::fromString('02IiNr0G8viOVKk0kQOokUeQle4I04rVnXsw9'));
        }
        $authProvider->getTokenId()->willReturn(Uuid::fromString('02IiNr0G8viOVKk93ZnAQKjaaGuTdNF728q5W'));

        return new GroupChangeAuditEventListener($authProvider->reveal(), $logger);
    }

    private function buildGroupNode(): NodeElement
    {
        return (new NodeElement())
            ->setId(Uuid::fromString('02IiNr0G8viOVKjTVopQ38JnV8hXStL6rA4Kf'))
            ->setLabel('Group');
    }

    public function testMergeOfGroupNodeIsLogged(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->notice(
            'Group node was updated.',
            [
                'elementId' => '02IiNr0G8viOVKjTVopQ38JnV8hXStL6rA4Kf',
                'client' => [
                    'user' => '02IiNr0G8viOVKk0kQOokUeQle4I04rVnXsw9',
                    'token' => '02IiNr0G8viOVKk93ZnAQKjaaGuTdNF728q5W',
                ],
            ]
        )->shouldBeCalledOnce();

        $listener = $this->buildListener($logger->reveal());
        $listener->onElementPostMerge(new ElementPostMergeEvent($this->buildGroupNode()));
    }

    public function testDeleteOfGroupNodeIsLogged(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->notice(
            'Group node was deleted.',
            [
                'elementId' => '02IiNr0G8viOVKjTVopQ38JnV8hXStL6rA4Kf',
                'client' => [
                    'user' => '02IiNr0G8viOVKk0kQOokUeQle4I04rVnXsw9',
                    'token' => '02IiNr0G8viOVKk93ZnAQKjaaGuTdNF728q5W',
                ],
            ]
        )->shouldBeCalledOnce();

        $listener = $this->buildListener($logger->reveal());
        $listener->onElementPostDelete(new ElementPostDeleteEvent($this->buildGroupNode()));
    }

    public function testAnonymousUserIsLoggedAsAnonymous(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->notice(
            'Group node was updated.',
            [
                'elementId' => '02IiNr0G8viOVKjTVopQ38JnV8hXStL6rA4Kf',
                'client' => [
                    'user' => 'anonymous',
                    'token' => '02IiNr0G8viOVKk93ZnAQKjaaGuTdNF728q5W',
                ],
            ]
        )->shouldBeCalledOnce();

        $listener = $this->buildListener($logger->reveal(), isAnonymous: true);
        $listener->onElementPostMerge(new ElementPostMergeEvent($this->buildGroupNode()));
    }

    public function testMergeOfNonGroupNodeIsIgnored(): void
    {
        $node = (new NodeElement())->setId(Uuid::fromString('02IiNr0G8viOVKjboyDliyOxJlXj6Bii5l1U2'))->setLabel('Data');

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->notice(Argument::cetera())->shouldNotBeCalled();

        $listener = $this->buildListener($logger->reveal());
        $listener->onElementPostMerge(new ElementPostMergeEvent($node));
    }

    public function testDeleteOfNonGroupNodeIsIgnored(): void
    {
        $node = (new NodeElement())->setId(Uuid::fromString('02IiNr0G8viOVKjboyDliyOxJlXj6Bii5l1U2'))->setLabel('Data');

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->notice(Argument::cetera())->shouldNotBeCalled();

        $listener = $this->buildListener($logger->reveal());
        $listener->onElementPostDelete(new ElementPostDeleteEvent($node));
    }

    public function testRelationElementIsIgnored(): void
    {
        $relation = (new RelationElement())
            ->setId(Uuid::fromString('02IiNr0G8viOVKjk87c7OoU78ONujU6JKLydP'))
            ->setType('OWNS');

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->notice(Argument::cetera())->shouldNotBeCalled();

        $listener = $this->buildListener($logger->reveal());
        $listener->onElementPostMerge(new ElementPostMergeEvent($relation));
    }

    public function testGroupNodeWithoutLabelIsIgnored(): void
    {
        $node = (new NodeElement())->setId(Uuid::fromString('02IiNr0G8viOVKjsRH0T4eZGx1E6MmTuYwvmm'));

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->notice(Argument::cetera())->shouldNotBeCalled();

        $listener = $this->buildListener($logger->reveal());
        $listener->onElementPostMerge(new ElementPostMergeEvent($node));
    }

    public function testGroupNodeWithoutIdIsIgnored(): void
    {
        $node = (new NodeElement())->setLabel('Group');

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->notice(Argument::cetera())->shouldNotBeCalled();

        $listener = $this->buildListener($logger->reveal());
        $listener->onElementPostMerge(new ElementPostMergeEvent($node));
    }
}
