<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class GetIndexController extends AbstractController
{
    public function __construct(private CypherEntityManager $cypherEntityManager)
    {
    }

    #[Route(
        '/',
        name: 'getIndex',
        methods: ['GET']
    )]
    public function getIndex(): Response
    {
        return new Response('it worked :D');
    }
}
