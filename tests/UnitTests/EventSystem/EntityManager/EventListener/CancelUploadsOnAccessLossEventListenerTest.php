<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\EntityManager\EventListener;

use App\EventSystem\EntityManager\Event\ElementPostDeleteEvent;
use App\EventSystem\EntityManager\Event\ElementPostMergeEvent;
use App\EventSystem\EntityManager\EventListener\CancelUploadsOnAccessLossEventListener;
use App\Service\UploadCancellationService;
use App\Type\NodeElement;
use App\Type\RelationElement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[Small]
#[CoversClass(CancelUploadsOnAccessLossEventListener::class)]
class CancelUploadsOnAccessLossEventListenerTest extends TestCase
{
    use ProphecyTrait;

    private function createResponseEvent(int $requestType = HttpKernelInterface::MAIN_REQUEST): ResponseEvent
    {
        return new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request(),
            $requestType,
            new Response()
        );
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function relationTypeProvider(): array
    {
        return [
            'OWNS' => ['OWNS', true],
            'IS_IN_GROUP' => ['IS_IN_GROUP', true],
            'HAS_UPDATE_ACCESS' => ['HAS_UPDATE_ACCESS', true],
            'unrelated relation' => ['LIKES', false],
        ];
    }

    #[DataProvider('relationTypeProvider')]
    public function testDeletedRelationTriggersReviewOnResponse(string $type, bool $expectsReview): void
    {
        $service = $this->prophesize(UploadCancellationService::class);
        $service->cancelUploadsWithoutAccess()->shouldBeCalledTimes($expectsReview ? 1 : 0)->willReturn(0);
        $listener = new CancelUploadsOnAccessLossEventListener($service->reveal());

        $listener->onElementPostDelete(new ElementPostDeleteEvent((new RelationElement())->setType($type)));
        $listener->onResponse($this->createResponseEvent());
        // the review happens only once per marking
        $listener->onResponse($this->createResponseEvent());
    }

    #[DataProvider('relationTypeProvider')]
    public function testMergedRelationTriggersReviewOnResponse(string $type, bool $expectsReview): void
    {
        $service = $this->prophesize(UploadCancellationService::class);
        $service->cancelUploadsWithoutAccess()->shouldBeCalledTimes($expectsReview ? 1 : 0)->willReturn(0);
        $listener = new CancelUploadsOnAccessLossEventListener($service->reveal());

        $listener->onElementPostMerge(new ElementPostMergeEvent((new RelationElement())->setType($type)));
        $listener->onResponse($this->createResponseEvent());
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function nodeLabelProvider(): array
    {
        return ['User' => ['User', true], 'Group' => ['Group', true], 'Data' => ['Data', false], 'Upload' => ['Upload', false]];
    }

    #[DataProvider('nodeLabelProvider')]
    public function testDeletedNodeTriggersReviewOnlyForUsersAndGroups(string $label, bool $expectsReview): void
    {
        $service = $this->prophesize(UploadCancellationService::class);
        $service->cancelUploadsWithoutAccess()->shouldBeCalledTimes($expectsReview ? 1 : 0)->willReturn(0);
        $listener = new CancelUploadsOnAccessLossEventListener($service->reveal());

        $listener->onElementPostDelete(new ElementPostDeleteEvent((new NodeElement())->setLabel($label)));
        $listener->onResponse($this->createResponseEvent());
    }

    public function testMergedNodeNeverTriggersReview(): void
    {
        $service = $this->prophesize(UploadCancellationService::class);
        $service->cancelUploadsWithoutAccess()->shouldNotBeCalled();
        $listener = new CancelUploadsOnAccessLossEventListener($service->reveal());

        $listener->onElementPostMerge(new ElementPostMergeEvent((new NodeElement())->setLabel('User')));
        $listener->onResponse($this->createResponseEvent());
    }

    public function testSubRequestsDoNotConsumeTheReview(): void
    {
        $service = $this->prophesize(UploadCancellationService::class);
        $service->cancelUploadsWithoutAccess()->shouldBeCalledOnce()->willReturn(0);
        $listener = new CancelUploadsOnAccessLossEventListener($service->reveal());

        $listener->onElementPostDelete(new ElementPostDeleteEvent((new RelationElement())->setType('OWNS')));
        $listener->onResponse($this->createResponseEvent(HttpKernelInterface::SUB_REQUEST));
        $listener->onResponse($this->createResponseEvent());
    }
}
