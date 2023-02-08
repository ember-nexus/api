<?php

namespace App\Controller\Problem;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ClientUnauthorizedController extends AbstractController
{
    public function __construct(
    ) {
    }

    #[Route(
        '/problem/401-unauthorized',
        name: 'problem-client-unauthorized',
        methods: ['GET']
    )]
    public function clientUnauthorized(): Response
    {
        return new Response('some info for 401 errors lol');
    }
}
