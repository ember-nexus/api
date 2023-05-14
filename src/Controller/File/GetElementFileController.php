<?php

namespace App\Controller\File;

use App\Exception\NotImplementedException;
use App\Helper\Regex;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GetElementFileController extends AbstractController
{
    #[Route(
        '/{uuid}/file',
        name: 'getElementFile',
        requirements: [
            'uuid' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['GET']
    )]
    public function getElementFile(string $uuid, Request $request): Response
    {
        throw new NotImplementedException();
    }
}
