<?php

declare(strict_types=1);

namespace App\Factory\Exception;

use App\Exception\Client400IncompleteMutualDependencyException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Client400IncompleteMutualDependencyExceptionFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
    ) {
    }

    /**
     * Returns an exception in the format of "Endpoint has mutual dependency on property '%s'. While property '%s' is set, property '%s' is missing.".
     *
     * @param string[] $properties
     * @param string[] $setProperties
     * @param string[] $missingProperties
     */
    public function createFromTemplate(array $properties, array $setProperties, array $missingProperties): Client400IncompleteMutualDependencyException
    {
        if (count($properties) < 2) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Mutual dependency requires at least two properties.');
        }

        $message = sprintf(
            "Endpoint has mutual dependency on properties '%s' and '%s'.",
            join("', '", array_slice($properties, 0, -1)),
            end($properties)
        );

        if (0 === count($setProperties)) {
            $message .= ' While no property is set,';
        } elseif (1 === count($setProperties)) {
            $message .= sprintf(
                " While property '%s' is set,",
                $setProperties[0]
            );
        } else {
            $message .= sprintf(
                " While properties '%s' and '%s' are set,",
                join("', '", array_slice($setProperties, 0, -1)),
                end($setProperties)
            );
        }

        if (0 === count($missingProperties)) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('At least one missing property is required.');
        } elseif (1 === count($missingProperties)) {
            $message .= sprintf(
                " property '%s' is missing.",
                $missingProperties[0]
            );
        } else {
            $message .= sprintf(
                " properties '%s' and '%s' are missing.",
                join("', '", array_slice($missingProperties, 0, -1)),
                end($missingProperties)
            );
        }

        return new Client400IncompleteMutualDependencyException(
            $this->urlGenerator->generate(
                'exception-detail',
                [
                    'code' => '400',
                    'name' => 'incomplete-mutual-dependency',
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            detail: $message
        );
    }
}
