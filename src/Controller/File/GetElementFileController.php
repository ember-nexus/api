<?php

declare(strict_types=1);

namespace App\Controller\File;

use App\Factory\Exception\Server501NotImplementedExceptionFactory;
use App\Helper\Regex;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class GetElementFileController extends AbstractController
{
    public function __construct(
        private Server501NotImplementedExceptionFactory $server501NotImplementedExceptionFactory
    ) {
    }

    #[Route(
        '/{id}/file',
        name: 'get-element-file',
        requirements: [
            'id' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['GET']
    )]
    public function getElementFile(string $id, Request $request): Response
    {
        throw $this->server501NotImplementedExceptionFactory->createFromTemplate();
    }
}
