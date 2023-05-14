<?php

namespace App\Controller\File;

use App\Exception\NotImplementedException;
use App\Helper\Regex;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PatchElementFileController extends AbstractController
{
    #[Route(
        '/{uuid}/file',
        name: 'patchElementFile',
        requirements: [
            'uuid' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['PATCH']
    )]
    public function patchElementFile(string $uuid, Request $request): Response
    {
        throw new NotImplementedException();
    }
}
