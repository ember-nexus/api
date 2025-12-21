<?php

declare(strict_types=1);

namespace App\Controller\Error;

use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Response\TextResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function Safe\preg_match;

class ExceptionDetailController extends AbstractController
{
    public function __construct(
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory,
    ) {
    }

    #[Route(
        '/error/{code}/{name}',
        name: 'exception-detail',
        methods: ['GET']
    )]
    public function exceptionDetail(string $code, string $name): Response
    {
        if (preg_match('/[^0-9]/', $code)) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }
        if (preg_match('/[^a-z-]/', $name)) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $errorContentPath = sprintf(
            '%s/assets/error-%s-%s.txt',
            __DIR__,
            $code,
            $name
        );
        if (!file_exists($errorContentPath)) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $emberNexusApiLogo = \Safe\file_get_contents(__DIR__.'/assets/ember-nexus-api-logo.txt');
        $content = \Safe\file_get_contents($errorContentPath);

        return new TextResponse(sprintf(
            '%s%s',
            $emberNexusApiLogo,
            $content
        ));
    }
}
