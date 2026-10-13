<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\EntityManager\EventListener;

use App\EventSystem\EntityManager\Event\ElementPostDeleteEvent;
use App\EventSystem\EntityManager\Event\ElementPostMergeEvent;
use App\EventSystem\EntityManager\EventListener\GroupChangeAuditEventListener;
use App\Security\AuthProvider;
use App\Type\NodeElement;
use App\Type\RelationElement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

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
            $authProvider->getUserId()->willReturn(Uuid::fromString('55555555-5555-5555-5555-555555555555'));
        }
        $authProvider->getTokenId()->willReturn(Uuid::fromString('66666666-6666-6666-6666-666666666666'));

        return new GroupChangeAuditEventListener($authProvider->reveal(), $logger);
    }

    private function buildGroupNode(): NodeElement
    {
        return (new NodeElement())
            ->setId(Uuid::fromString('11111111-1111-1111-1111-111111111111'))
            ->setLabel('Group');
    }

    public function testMergeOfGroupNodeIsLogged(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->notice(
            'Group node was updated.',
            [
                'elementId' => '11111111-1111-1111-1111-111111111111',
                'client' => [
                    'user' => '55555555-5555-5555-5555-555555555555',
                    'token' => '66666666-6666-6666-6666-666666666666',
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
                'elementId' => '11111111-1111-1111-1111-111111111111',
                'client' => [
                    'user' => '55555555-5555-5555-5555-555555555555',
                    'token' => '66666666-6666-6666-6666-666666666666',
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
                'elementId' => '11111111-1111-1111-1111-111111111111',
                'client' => [
                    'user' => 'anonymous',
                    'token' => '66666666-6666-6666-6666-666666666666',
                ],
            ]
        )->shouldBeCalledOnce();

        $listener = $this->buildListener($logger->reveal(), isAnonymous: true);
        $listener->onElementPostMerge(new ElementPostMergeEvent($this->buildGroupNode()));
    }

    public function testMergeOfNonGroupNodeIsIgnored(): void
    {
        $node = (new NodeElement())->setId(Uuid::fromString('22222222-2222-2222-2222-222222222222'))->setLabel('Data');

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->notice(Argument::cetera())->shouldNotBeCalled();

        $listener = $this->buildListener($logger->reveal());
        $listener->onElementPostMerge(new ElementPostMergeEvent($node));
    }

    public function testDeleteOfNonGroupNodeIsIgnored(): void
    {
        $node = (new NodeElement())->setId(Uuid::fromString('22222222-2222-2222-2222-222222222222'))->setLabel('Data');

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->notice(Argument::cetera())->shouldNotBeCalled();

        $listener = $this->buildListener($logger->reveal());
        $listener->onElementPostDelete(new ElementPostDeleteEvent($node));
    }

    public function testRelationElementIsIgnored(): void
    {
        $relation = (new RelationElement())
            ->setId(Uuid::fromString('33333333-3333-3333-3333-333333333333'))
            ->setType('OWNS');

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->notice(Argument::cetera())->shouldNotBeCalled();

        $listener = $this->buildListener($logger->reveal());
        $listener->onElementPostMerge(new ElementPostMergeEvent($relation));
    }

    public function testGroupNodeWithoutLabelIsIgnored(): void
    {
        $node = (new NodeElement())->setId(Uuid::fromString('44444444-4444-4444-4444-444444444444'));

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
