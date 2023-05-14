<?php

namespace App\Controller\WebDAV;

use App\Exception\NotImplementedException;
use App\Helper\Regex;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MoveElementController extends AbstractController
{
    #[Route(
        '/{uuid}',
        name: 'moveElement',
        requirements: [
            'uuid' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['MOVE']
    )]
    public function moveElement(string $uuid, Request $request): Response
    {
        throw new NotImplementedException();
    }
}
