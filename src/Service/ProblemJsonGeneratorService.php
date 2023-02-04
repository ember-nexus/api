<?php

namespace App\Service;

use App\Response\ProblemJsonResponse;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProblemJsonGeneratorService
{
    public function __construct(
        private ParameterBagInterface $bag,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function createProblemJsonFor404(): ProblemJsonResponse
    {
        $type = $this->urlGenerator->generate('problem-404-not-found', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $links = $this->bag->get('errorDocumentationLinks');
        if (array_key_exists('404-not-found', $links)) {
            $type = $links['404-not-found'];
        }

        return new ProblemJsonResponse(
            [
                'type' => $type,
                'title' => 'requested element was not found',
                'status' => 404,
            ],
            404
        );
    }
}
