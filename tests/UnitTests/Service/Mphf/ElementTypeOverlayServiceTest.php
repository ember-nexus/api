<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service\Mphf;

use App\Factory\Type\RedisKeyFactory;
use App\Service\Mphf\ElementTypeOverlayService;
use App\Type\MphfOverlaySlot;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

#[Small]
#[CoversClass(ElementTypeOverlayService::class)]
class ElementTypeOverlayServiceTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $redis;
    private ElementTypeOverlayService $service;

    protected function setUp(): void
    {
        $this->redis = $this->prophesize(Client::class);
        $this->service = new ElementTypeOverlayService($this->redis->reveal(), new RedisKeyFactory());
    }

    public function testRegisterRunsAtomicScriptWhichAlsoAddsTheIdToTheElementIdFilter(): void
    {
        $this->redis->eval(
            Argument::that(static fn (string $script): bool => str_contains($script, "redis.pcall('BF.ADD'")),
            6,
            'mphf-overlay:active',
            'mphf-overlay:A',
            'mphf-overlay:B',
            'element-id-filter:active',
            'element-id-filter:A',
            'element-id-filter:B',
            'id1',
            'Data'
        )->shouldBeCalledOnce();

        $this->service->register('id1', 'Data');
    }

    public function testSwitchActiveReturnsNewSlot(): void
    {
        $this->redis->eval(Argument::type('string'), 3, 'mphf-overlay:active', 'mphf-overlay:A', 'mphf-overlay:B')->willReturn('B');

        $this->assertSame(MphfOverlaySlot::B, $this->service->switchActive());
    }

    public function testGetActiveSlotDefaultsToA(): void
    {
        $this->redis->get('mphf-overlay:active')->willReturn(null, 'B', 'A');

        $this->assertSame(MphfOverlaySlot::A, $this->service->getActiveSlot());
        $this->assertSame(MphfOverlaySlot::B, $this->service->getActiveSlot());
        $this->assertSame(MphfOverlaySlot::A, $this->service->getActiveSlot());
    }

    public function testClearInactiveUnlinksTheOtherKey(): void
    {
        $this->redis->get('mphf-overlay:active')->willReturn('B');
        $this->redis->unlink(['mphf-overlay:A'])->shouldBeCalledOnce();

        $this->service->clearInactive();
    }

    public function testExpireInactiveLetsRedisDeleteTheOtherKey(): void
    {
        $this->redis->get('mphf-overlay:active')->willReturn('B');
        $this->redis->expire('mphf-overlay:A', 300)->shouldBeCalledOnce();

        $this->service->expireInactive(300);
    }

    public function testClearAllUnlinksEverything(): void
    {
        $this->redis->unlink(['mphf-overlay:A', 'mphf-overlay:B', 'mphf-overlay:active'])->shouldBeCalledOnce();

        $this->service->clearAll();
    }

    /**
     * @return array<string, array{array<int, ?string>, ?string}>
     */
    public static function lookupProvider(): array
    {
        return [
            'active A wins' => [['New', 'Old', 'A'], 'New'],
            'active B wins' => [['Old', 'New', 'B'], 'New'],
            'falls back to inactive' => [[null, 'Old', 'A'], 'Old'],
            'no pointer means A' => [[null, 'B-entry', null], 'B-entry'],
            'unknown' => [[null, null, 'A'], null],
            'empty value is a miss' => [['', null, 'A'], null],
        ];
    }

    /**
     * @param array<int, ?string> $replies
     */
    #[DataProvider('lookupProvider')]
    public function testLookup(array $replies, ?string $expected): void
    {
        $this->redis->pipeline(Argument::any())->willReturn($replies);

        $this->assertSame($expected, $this->service->lookup('id1'));
    }

    public function testLookupManyReadsAllIdsInOneRoundTripAndPrefersTheActiveKey(): void
    {
        $this->redis->pipeline(Argument::any())->shouldBeCalledOnce()->willReturn([
            ['old-a', null, 'a-only', null],
            ['new-a', 'b-only', null, null],
            'B',
        ]);

        $this->assertSame(
            ['id1' => 'new-a', 'id2' => 'b-only', 'id3' => 'a-only'],
            $this->service->lookupMany(['id1', 'id2', 'id3', 'id4'])
        );
    }

    public function testLookupManyWithoutIdsDoesNotCallRedis(): void
    {
        $this->redis->pipeline(Argument::any())->shouldNotBeCalled();

        $this->assertSame([], $this->service->lookupMany([]));
    }
}
