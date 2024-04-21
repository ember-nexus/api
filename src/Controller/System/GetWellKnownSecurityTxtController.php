<?php

declare(strict_types=1);

namespace App\Controller\System;

use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Response\TextResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GetWellKnownSecurityTxtController extends AbstractController
{
    public const string PATH_TO_WELL_KNOWN_SECURITY_TXT = '/well-known-security.txt';

    public function __construct(
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory
    ) {
    }

    #[Route(
        '/.well-known/security.txt',
        name: 'get-well-known-security-txt',
        methods: ['GET']
    )]
    public function getWellKnownSecurityTxt(): Response
    {
        if (!file_exists(self::PATH_TO_WELL_KNOWN_SECURITY_TXT)) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }
        $wellKnownSecurityTxtContent = \Safe\file_get_contents(self::PATH_TO_WELL_KNOWN_SECURITY_TXT);

        return new TextResponse($wellKnownSecurityTxtContent);
    }
}
