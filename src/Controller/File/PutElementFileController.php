<?php

namespace App\Controller\File;

use App\Exception\NotImplementedException;
use App\Helper\Regex;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PutElementFileController extends AbstractController
{
    #[Route(
        '/{uuid}/file',
        name: 'putElementFile',
        requirements: [
            'uuid' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['PUT']
    )]
    public function putElementFile(string $uuid, Request $request): Response
    {
        throw new NotImplementedException();
    }
}
