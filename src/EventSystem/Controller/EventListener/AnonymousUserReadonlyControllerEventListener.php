<?php

declare(strict_types=1);

namespace App\EventSystem\Controller\EventListener;

use App\Attribute\EndpointCanBeUsedByReadonlyAnonymousUser;
use App\Attribute\EndpointWithoutAuthentication;
use App\Factory\Exception\Client403ForbiddenExceptionFactory;
use App\Security\AuthProvider;
use LogicException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * Enforces `ANONYMOUS_USER_READONLY`: once active, the anonymous user may only call safe (`GET`/`HEAD`) endpoints,
 * plus any endpoint explicitly marked with {@see EndpointCanBeUsedByReadonlyAnonymousUser} or
 * {@see EndpointWithoutAuthentication}.
 */
class AnonymousUserReadonlyControllerEventListener
{
    /**
     * @var string[]
     */
    private const array SAFE_METHODS = ['GET', 'HEAD'];

    public function __construct(
        private ParameterBagInterface $bag,
        private AuthProvider $authProvider,
        private Client403ForbiddenExceptionFactory $client403ForbiddenExceptionFactory,
    ) {
    }

    #[AsEventListener(priority: 384)]
    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        if (in_array($event->getRequest()->getMethod(), self::SAFE_METHODS, true)) {
            return;
        }
        if (!$this->isAnonymousUserReadonly()) {
            return;
        }
        if (!$this->authProvider->isAnonymous()) {
            return;
        }
        if (count($event->getAttributes(EndpointCanBeUsedByReadonlyAnonymousUser::class)) > 0) {
            return;
        }
        if (count($event->getAttributes(EndpointWithoutAuthentication::class)) > 0) {
            return;
        }

        throw $this->client403ForbiddenExceptionFactory->createFromTemplate('Anonymous users only have read access; an authenticated token is required for this action.');
    }

    private function isAnonymousUserReadonly(): bool
    {
        $isAnonymousUserReadonly = $this->bag->get('isAnonymousUserReadonly');
        if (!is_bool($isAnonymousUserReadonly)) {
            throw new LogicException(sprintf('Expected "isAnonymousUserReadonly" to be of type boolean, got %s.', get_debug_type($isAnonymousUserReadonly)));
        }

        return $isAnonymousUserReadonly;
    }
}
