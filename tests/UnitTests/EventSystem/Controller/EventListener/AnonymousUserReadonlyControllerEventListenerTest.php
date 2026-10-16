<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\Controller\EventListener;

use App\Attribute\EndpointCanBeUsedByReadonlyAnonymousUser;
use App\Attribute\EndpointWithoutAuthentication;
use App\EventSystem\Controller\EventListener\AnonymousUserReadonlyControllerEventListener;
use App\Exception\Client403ForbiddenException;
use App\Factory\Exception\Client403ForbiddenExceptionFactory;
use App\Security\AuthProvider;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Small]
#[CoversClass(AnonymousUserReadonlyControllerEventListener::class)]
class AnonymousUserReadonlyControllerEventListenerTest extends TestCase
{
    use ProphecyTrait;

    private function buildListener(bool $isAnonymousUserReadonly, bool $isAnonymous): AnonymousUserReadonlyControllerEventListener
    {
        $bag = $this->prophesize(ParameterBagInterface::class);
        $bag->get('isAnonymousUserReadonly')->willReturn($isAnonymousUserReadonly);

        $authProvider = $this->prophesize(AuthProvider::class);
        $authProvider->isAnonymous()->willReturn($isAnonymous);

        return new AnonymousUserReadonlyControllerEventListener(
            $bag->reveal(),
            $authProvider->reveal(),
            new Client403ForbiddenExceptionFactory($this->prophesize(UrlGeneratorInterface::class)->reveal()),
        );
    }

    private function buildEvent(string $method, ?callable $controller = null): ControllerEvent
    {
        return new ControllerEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $controller ?? function () {},
            Request::create('/', $method),
            HttpKernelInterface::MAIN_REQUEST
        );
    }

    #[DataProvider('safeMethodProvider')]
    public function testSafeMethodsAreAlwaysAllowed(string $method): void
    {
        self::expectNotToPerformAssertions();

        $listener = $this->buildListener(isAnonymousUserReadonly: true, isAnonymous: true);
        $listener->onKernelController($this->buildEvent($method));
    }

    /**
     * @return array<string, string[]>
     */
    public static function safeMethodProvider(): array
    {
        return [
            'GET' => ['GET'],
            'HEAD' => ['HEAD'],
        ];
    }

    public function testWriteIsAllowedWhenReadonlyIsDisabled(): void
    {
        self::expectNotToPerformAssertions();

        $listener = $this->buildListener(isAnonymousUserReadonly: false, isAnonymous: true);
        $listener->onKernelController($this->buildEvent('POST'));
    }

    public function testWriteIsAllowedWhenUserIsNotAnonymous(): void
    {
        self::expectNotToPerformAssertions();

        $listener = $this->buildListener(isAnonymousUserReadonly: true, isAnonymous: false);
        $listener->onKernelController($this->buildEvent('POST'));
    }

    public function testWriteIsAllowedWhenEndpointHasExemptingAttribute(): void
    {
        self::expectNotToPerformAssertions();

        $controller = #[EndpointCanBeUsedByReadonlyAnonymousUser]
        fn () => true;

        $listener = $this->buildListener(isAnonymousUserReadonly: true, isAnonymous: true);
        $listener->onKernelController($this->buildEvent('POST', $controller));
    }

    public function testWriteIsAllowedWhenEndpointIsWithoutAuthentication(): void
    {
        self::expectNotToPerformAssertions();

        $controller = #[EndpointWithoutAuthentication]
        fn () => true;

        $listener = $this->buildListener(isAnonymousUserReadonly: true, isAnonymous: true);
        $listener->onKernelController($this->buildEvent('POST', $controller));
    }

    public function testWriteIsBlockedForAnonymousUserWhenReadonlyIsEnabled(): void
    {
        $bag = $this->prophesize(ParameterBagInterface::class);
        $bag->get('isAnonymousUserReadonly')->willReturn(true);

        $authProvider = $this->prophesize(AuthProvider::class);
        $authProvider->isAnonymous()->willReturn(true);

        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())->willReturn('https://example.com/error/403/forbidden');

        $listener = new AnonymousUserReadonlyControllerEventListener(
            $bag->reveal(),
            $authProvider->reveal(),
            new Client403ForbiddenExceptionFactory($urlGenerator->reveal()),
        );

        $this->expectException(Client403ForbiddenException::class);
        $listener->onKernelController($this->buildEvent('POST'));
    }

    public function testThrowsIfIsAnonymousUserReadonlyParameterIsNotBoolean(): void
    {
        $bag = $this->prophesize(ParameterBagInterface::class);
        $bag->get('isAnonymousUserReadonly')->willReturn('not-a-boolean');

        $authProvider = $this->prophesize(AuthProvider::class);
        $authProvider->isAnonymous()->shouldNotBeCalled();

        $listener = new AnonymousUserReadonlyControllerEventListener(
            $bag->reveal(),
            $authProvider->reveal(),
            new Client403ForbiddenExceptionFactory($this->prophesize(UrlGeneratorInterface::class)->reveal()),
        );

        $this->expectException(LogicException::class);
        $listener->onKernelController($this->buildEvent('POST'));
    }
}
