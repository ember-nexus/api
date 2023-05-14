<?php

namespace App\Controller\WebDAV;

use App\Exception\NotImplementedException;
use App\Helper\Regex;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MkcolElementController extends AbstractController
{
    #[Route(
        '/{uuid}',
        name: 'mkcolElement',
        requirements: [
            'uuid' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['MKCOL']
    )]
    public function mkcolElement(string $uuid, Request $request): Response
    {
        throw new NotImplementedException();
    }
}
