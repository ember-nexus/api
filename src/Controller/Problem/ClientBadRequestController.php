<?php

namespace App\Controller\Problem;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ClientBadRequestController extends AbstractController
{
    public function __construct(
    ) {
    }

    #[Route(
        '/problem/400-bad-request',
        name: 'problem-client-bad-request',
        methods: ['GET']
    )]
    public function clientBadRequest(): Response
    {
        return new Response('some info for 400 errors lol');
    }
}
