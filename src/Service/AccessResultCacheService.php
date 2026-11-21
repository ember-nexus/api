<?php

declare(strict_types=1);

namespace App\Service;

use App\Type\AccessType;
use App\Type\Uuid;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Remembers the answers of access checks for the rest of the request, as several layers check the same access (e.g.
 * the etag handling and the controller). Every layer still asks the AccessChecker on its own, so none depends on
 * another one having checked. Changing elements may change access, therefore the ElementManager forgets all answers
 * whenever it flushes.
 */
class AccessResultCacheService implements ResetInterface
{
    /**
     * @var array<string, bool>
     */
    private array $results = [];

    /**
     * @SuppressWarnings("PHPMD.BooleanGetMethodName")
     */
    public function get(Uuid $userId, Uuid $elementId, AccessType $accessType): ?bool
    {
        return $this->results[$this->key($userId, $elementId, $accessType)] ?? null;
    }

    public function set(Uuid $userId, Uuid $elementId, AccessType $accessType, bool $hasAccess): void
    {
        $this->results[$this->key($userId, $elementId, $accessType)] = $hasAccess;
    }

    public function forget(): void
    {
        $this->results = [];
    }

    public function reset(): void
    {
        $this->forget();
    }

    private function key(Uuid $userId, Uuid $elementId, AccessType $accessType): string
    {
        return $userId->toString().'|'.$elementId->toString().'|'.$accessType->value;
    }
}
