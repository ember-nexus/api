<?php

namespace App\Service;

use App\Response\ProblemJsonResponse;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProblemJsonGeneratorService
{
    public function __construct(
        private ParameterBagInterface $bag,
        private UrlGeneratorInterface $urlGenerator,
        private KernelInterface $kernel
    ) {
    }

    public function createProblemJsonFor404(
        ?string $prodDetail = null,
        ?string $devDetail = null
    ): ProblemJsonResponse {
        $type = $this->urlGenerator->generate('problem-404-not-found', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $links = $this->bag->get('errorDocumentationLinks');
        if (array_key_exists('404-not-found', $links)) {
            if ($links['404-not-found']) {
                $type = $links['404-not-found'];
            }
        }

        $data = [
            'type' => $type,
            'title' => 'Unable to find requested element',
            'status' => 404,
        ];

        if ($this->kernel->isDebug()) {
            if ($devDetail) {
                $data['detail'] = $devDetail;
            }
        } else {
            if ($prodDetail) {
                $data['detail'] = $prodDetail;
            }
        }

        return new ProblemJsonResponse(
            $data,
            404
        );
    }

    public function createProblemJsonFor403(
        ?string $prodDetail = null,
        ?string $devDetail = null
    ): ProblemJsonResponse {
        $type = $this->urlGenerator->generate('problem-403-forbidden', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $links = $this->bag->get('errorDocumentationLinks');
        if (array_key_exists('403-forbidden', $links)) {
            if ($links['403-forbidden']) {
                $type = $links['403-forbidden'];
            }
        }

        $data = [
            'type' => $type,
            'title' => 'Forbidden to perform the action',
            'status' => 403,
        ];

        if ($this->kernel->isDebug()) {
            if ($devDetail) {
                $data['detail'] = $devDetail;
            }
        } else {
            if ($prodDetail) {
                $data['detail'] = $prodDetail;
            }
        }

        return new ProblemJsonResponse(
            $data,
            403
        );
    }
}
