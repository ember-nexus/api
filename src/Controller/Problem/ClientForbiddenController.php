<?php

namespace App\Controller\Problem;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ClientForbiddenController extends AbstractController
{
    public function __construct(
    ) {
    }

    #[Route(
        '/problem/403-forbidden',
        name: 'problem-client-forbidden',
        methods: ['GET']
    )]
    public function clientForbidden(): Response
    {
        return new Response('some info for 403 errors lol');
    }
}
