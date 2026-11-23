<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service\Mphf;

use App\Factory\Type\RedisKeyFactory;
use App\Service\Mphf\ElementIdFilterService;
use App\Type\MphfOverlaySlot;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;

#[Small]
#[CoversClass(ElementIdFilterService::class)]
class ElementIdFilterServiceTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<Client> */
    private ObjectProphecy $redis;

    protected function setUp(): void
    {
        $this->redis = $this->prophesize(Client::class);
    }

    private function create(bool $enabled = true): ElementIdFilterService
    {
        return new ElementIdFilterService($this->redis->reveal(), new RedisKeyFactory(), $enabled, 0.001);
    }

    /**
     * @return array<string, array{mixed, bool|null}>
     */
    public static function answerProvider(): array
    {
        return [
            'not in the filter' => [0, false],
            'might be in the filter' => [1, true],
            'filter is not complete or not active' => [-1, null],
            'unexpected answer' => ['nonsense', null],
            'nothing' => [null, null],
        ];
    }

    #[DataProvider('answerProvider')]
    public function testMightExistTrustsOnlyTheAnswersOfACompleteFilter(mixed $answer, ?bool $expected): void
    {
        $this->redis->eval(
            Argument::that(static fn (string $script): bool => str_contains($script, 'BF.EXISTS')),
            4,
            'element-id-filter:active',
            'element-id-filter:ready',
            'element-id-filter:A',
            'element-id-filter:B',
            'some-id'
        )->willReturn($answer);

        $this->assertSame($expected, $this->create()->mightExist('some-id'));
    }

    public function testMightExistIsUnknownIfRedisFailsOrCanNotDoBloomFilters(): void
    {
        $this->redis->eval(Argument::cetera())->willThrow(new RuntimeException("ERR unknown command 'BF.EXISTS'"));

        $this->assertNull($this->create()->mightExist('some-id'));
    }

    public function testDisabledFilterIsNeverAsked(): void
    {
        $this->redis->eval(Argument::cetera())->shouldNotBeCalled();

        $this->assertFalse($this->create(false)->isEnabled());
        $this->assertNull($this->create(false)->mightExist('some-id'));
    }

    public function testPrepareBuildReservesTheInactiveFilterAndWithdrawsTheTrust(): void
    {
        $this->redis->eval(
            Argument::that(static fn (string $script): bool => str_contains($script, 'BF.RESERVE') && str_contains($script, "redis.call('DEL', KEYS[4])")),
            4,
            'element-id-filter:active',
            'element-id-filter:A',
            'element-id-filter:B',
            'element-id-filter:ready',
            '0.001',
            '5000'
        )->willReturn('B')->shouldBeCalledOnce();

        $this->assertSame(MphfOverlaySlot::B, $this->create()->prepareBuild(5000));
    }

    public function testPrepareBuildReservesAtLeastAThousandIds(): void
    {
        $this->redis->eval(Argument::any(), 4, Argument::cetera())->will(static function (array $args) {
            return '1000' === $args[7] ? 'A' : 'wrong capacity';
        });

        $this->assertSame(MphfOverlaySlot::A, $this->create()->prepareBuild(3));
    }

    public function testAddManyAddsInChunks(): void
    {
        $ids = array_map(static fn (int $i): string => 'id'.$i, range(1, ElementIdFilterService::ADD_CHUNK_SIZE + 5));
        $this->redis->executeRaw(Argument::that(
            static fn (array $command): bool => 'BF.MADD' === $command[0] && 'element-id-filter:B' === $command[1] && ElementIdFilterService::ADD_CHUNK_SIZE + 2 === count($command)
        ))->shouldBeCalledOnce();
        $this->redis->executeRaw(Argument::that(
            static fn (array $command): bool => 'BF.MADD' === $command[0] && 7 === count($command) && 'id'.(ElementIdFilterService::ADD_CHUNK_SIZE + 5) === $command[6]
        ))->shouldBeCalledOnce();

        $this->create()->addMany(MphfOverlaySlot::B, $ids);
    }

    public function testAddManyWithoutIdsDoesNothing(): void
    {
        $this->redis->executeRaw(Argument::cetera())->shouldNotBeCalled();

        $this->create()->addMany(MphfOverlaySlot::A, []);
    }

    public function testFinishBuildMarksTheFilterCompleteAndDropsTheOtherOne(): void
    {
        $this->redis->set('element-id-filter:ready', 'B')->shouldBeCalledOnce();
        $this->redis->unlink(['element-id-filter:A'])->shouldBeCalledOnce();

        $this->create()->finishBuild(MphfOverlaySlot::B);
    }

    public function testClearRemovesEverything(): void
    {
        $this->redis->unlink(['element-id-filter:A', 'element-id-filter:B', 'element-id-filter:active', 'element-id-filter:ready'])->shouldBeCalledOnce();

        $this->create()->clear();
    }
}
