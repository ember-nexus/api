<?php

namespace App\Controller\Problem;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class Problem403Controller extends AbstractController
{
    public function __construct(
    ) {
    }

    #[Route(
        '/problem/403-forbidden',
        name: 'problem-403-forbidden',
        methods: ['GET']
    )]
    public function problem403NotFound(): Response
    {
        return new Response('some info for 403 errors lol');
    }
}
