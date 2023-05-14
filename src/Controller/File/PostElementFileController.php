<?php

namespace App\Controller\File;

use App\Exception\NotImplementedException;
use App\Helper\Regex;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PostElementFileController extends AbstractController
{
    #[Route(
        '/{uuid}/file',
        name: 'postElementFile',
        requirements: [
            'uuid' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['POST']
    )]
    public function postElementFile(string $uuid, Request $request): Response
    {
        throw new NotImplementedException();
    }
}
