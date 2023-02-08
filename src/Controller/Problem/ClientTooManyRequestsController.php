<?php

namespace App\Controller\Problem;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ClientTooManyRequestsController extends AbstractController
{
    public function __construct(
    ) {
    }

    #[Route(
        '/problem/429-too-many-requests',
        name: 'problem-client-too-many-requests',
        methods: ['GET']
    )]
    public function clientTooManyRequests(): Response
    {
        return new Response('some info for 429 errors lol');
    }
}
