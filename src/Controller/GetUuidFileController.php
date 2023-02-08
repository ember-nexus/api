<?php

namespace App\Controller;

use App\Helper\Regex;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GetUuidFileController extends AbstractController
{
    public function __construct()
    {
    }

    #[Route(
        '/{uuid}/file',
        name: 'getUuidFile',
        requirements: [
            'uuid' => Regex::UUID_V4,
        ],
        methods: ['GET']
    )]
    public function getUuidFile(string $uuid): Response
    {
        return new Response('it worked :D');
    }
}
