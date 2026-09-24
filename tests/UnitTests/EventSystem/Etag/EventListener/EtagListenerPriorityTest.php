<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\Etag\EventListener;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * The Redis listeners have to run before the live listeners, so that cached ETags are used if available.
 */
#[Small]
#[CoversNothing]
class EtagListenerPriorityTest extends TestCase
{
    private const string NAMESPACE = 'App\\EventSystem\\Etag\\EventListener\\';

    /**
     * @return array<string, array{string}>
     */
    public static function typeProvider(): array
    {
        $types = ['Element', 'File', 'ChildrenCollection', 'ParentsCollection', 'RelatedCollection', 'IndexCollection'];

        return array_combine($types, array_map(fn (string $type) => [$type], $types));
    }

    private function getPriority(string $class): int
    {
        $priorities = [];
        foreach ((new ReflectionClass($class))->getMethods() as $method) {
            foreach ($method->getAttributes(AsEventListener::class) as $attribute) {
                /** @var AsEventListener $listener */
                $listener = $attribute->newInstance();
                $priorities[] = $listener->priority;
            }
        }
        $this->assertCount(1, $priorities, sprintf('%s should register exactly one listener.', $class));

        return $priorities[0] ?? 0;
    }

    #[DataProvider('typeProvider')]
    public function testRedisListenerRunsBeforeLiveListener(string $type): void
    {
        $redisPriority = $this->getPriority(sprintf('%sRedis%sEtagEventListener', self::NAMESPACE, $type));
        $livePriority = $this->getPriority(sprintf('%sLive%sEtagEventListener', self::NAMESPACE, $type));

        $this->assertGreaterThan($livePriority, $redisPriority);
    }
}
