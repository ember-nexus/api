<?php

namespace App\Controller\Problem;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ClientNotFoundController extends AbstractController
{
    public function __construct(
    ) {
    }

    #[Route(
        '/problem/404-not-found',
        name: 'problem-client-not-found',
        methods: ['GET']
    )]
    public function clientNotFound(): Response
    {
        return new Response('some info for 404 errors lol');
    }
}
