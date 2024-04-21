<?php

namespace App\Controller\User;

use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client401UnauthorizedExceptionFactory;
use App\Response\NoContentResponse;
use App\Service\RequestUtilService;
use App\Service\SecurityUtilService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PostChangePasswordController extends AbstractController
{
    public function __construct(
        private RequestUtilService $requestUtilService,
        private SecurityUtilService $securityUtilService,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Client401UnauthorizedExceptionFactory $client401UnauthorizedExceptionFactory,
    ) {
    }

    #[Route(
        '/change-password',
        name: 'post-change-password',
        methods: ['POST']
    )]
    public function postChangePassword(Request $request): Response
    {
        $body = \Safe\json_decode($request->getContent(), true);
        $data = $this->requestUtilService->getDataFromBody($body);

        $this->requestUtilService->validateTypeFromBody('ActionChangePassword', $body);

        $newPassword = $this->requestUtilService->getStringFromBody('newPassword', $body);
        $currentPassword = $this->requestUtilService->getStringFromBody('currentPassword', $body);
        $this->validateNewPasswordIsDifferentFromCurrentPassword($newPassword, $currentPassword);

        $uniqueUserIdentifier = $this->requestUtilService->getUniqueUserIdentifierFromBodyAndData($body, $data);

        $userNode = $this->securityUtilService->findUserByUniqueUserIdentifier($uniqueUserIdentifier);
        $userId = $userNode->getIdentifier();
        if (null === $userId) {
            throw $this->client401UnauthorizedExceptionFactory->createFromTemplate();
        }

        $this->securityUtilService->validateUserIsNotAnonymousUser($userId);
        $this->securityUtilService->validatePasswordMatches($userNode, $currentPassword);
        $this->securityUtilService->changeUserPassword($userNode, $newPassword);

        return new NoContentResponse();
    }

    private function validateNewPasswordIsDifferentFromCurrentPassword(string $newPassword, string $currentPassword): void
    {
        if ($currentPassword === $newPassword) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('newPassword', 'password which is not identical to the old password', '<redacted>');
        }
    }
}
