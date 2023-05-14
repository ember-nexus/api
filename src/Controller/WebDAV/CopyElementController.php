<?php

namespace App\Controller\WebDAV;

use App\Exception\NotImplementedException;
use App\Helper\Regex;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CopyElementController extends AbstractController
{
    #[Route(
        '/{uuid}',
        name: 'copyElement',
        requirements: [
            'uuid' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['COPY']
    )]
    public function copyElement(string $uuid, Request $request): Response
    {
        throw new NotImplementedException();
    }
}
