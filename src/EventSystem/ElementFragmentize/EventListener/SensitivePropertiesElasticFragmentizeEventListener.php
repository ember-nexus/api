<?php

declare(strict_types=1);

namespace App\EventSystem\ElementFragmentize\EventListener;

use App\EventSystem\ElementFragmentize\Event\NodeElementFragmentizeEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Removes properties which are required for authentication from the Elasticsearch fragment.
 *
 * Elasticsearch documents are searchable and highlightable by every user which has search access to the element, so
 * password and token hashes must never be part of them. They stay in the other fragments, as authentication relies on
 * them.
 *
 * Must run after the generic property listener, which copies all properties to the Elasticsearch fragment.
 */
class SensitivePropertiesElasticFragmentizeEventListener
{
    /**
     * @var array<string, list<string>>
     */
    public const array SENSITIVE_PROPERTIES_BY_LABEL = [
        'User' => ['_passwordHash'],
        'Token' => ['hash', '_tokenHash'],
    ];

    #[AsEventListener(priority: -100)]
    public function onNodeElementFragmentizeEvent(NodeElementFragmentizeEvent $event): void
    {
        $label = $event->getNodeElement()->getLabel();
        if (null === $label || !array_key_exists($label, self::SENSITIVE_PROPERTIES_BY_LABEL)) {
            return;
        }

        $elasticFragment = $event->getElasticFragment();
        foreach (self::SENSITIVE_PROPERTIES_BY_LABEL[$label] as $propertyName) {
            $elasticFragment->removeProperty($propertyName);
        }
    }
}
