<?php

namespace App\tests\FeatureTests\Endpoint\User;

use App\Tests\FeatureTests\BaseRequestTestCase;

class PostChangePasswordTest extends BaseRequestTestCase
{
    private const string EMAIL = 'user@changePassword.user.endpoint.localhost.dev';
    private const string PASSWORD = '1234';
    private const string NEW_PASSWORD = 'abcd';

    public function testChangePassword(): void
    {
        $changePasswordResponse = $this->runPostRequest(
            '/change-password',
            null,
            [
                'type' => 'ActionChangePassword',
                'currentPassword' => self::PASSWORD,
                'newPassword' => self::NEW_PASSWORD,
                'uniqueUserIdentifier' => self::EMAIL,
            ]
        );
        $this->assertNoContentResponse($changePasswordResponse);

        $createTokenWithOldPasswordResponse = $this->runPostRequest(
            '/token',
            null,
            [
                'type' => 'Token',
                'uniqueUserIdentifier' => self::EMAIL,
                'password' => self::PASSWORD,
            ]
        );
        $this->assertIsProblemResponse($createTokenWithOldPasswordResponse, 401);

        $createTokenWithNewPasswordResponse = $this->runPostRequest(
            '/token',
            null,
            [
                'type' => 'Token',
                'uniqueUserIdentifier' => self::EMAIL,
                'password' => self::NEW_PASSWORD,
            ]
        );
        $this->assertSame(200, $createTokenWithNewPasswordResponse->getStatusCode());

        $changePasswordWithOldCredentialsFails = $this->runPostRequest(
            '/change-password',
            null,
            [
                'type' => 'ActionChangePassword',
                'currentPassword' => self::PASSWORD,
                'newPassword' => self::NEW_PASSWORD,
                'uniqueUserIdentifier' => self::EMAIL,
            ]
        );
        $this->assertIsProblemResponse($changePasswordWithOldCredentialsFails, 401);
    }

    public function testChangePasswordFailsForMissingUser(): void
    {
        $changePasswordWithOldCredentialsFails = $this->runPostRequest(
            '/change-password',
            null,
            [
                'type' => 'ActionChangePassword',
                'currentPassword' => '1234',
                'newPassword' => 'abcd',
                'uniqueUserIdentifier' => 'this-email-does-not-exist@localhost.dev',
            ]
        );
        $this->assertIsProblemResponse($changePasswordWithOldCredentialsFails, 401);
    }

    public function testChangePasswordFailsIfTypeIsMissing(): void
    {
        $changePasswordWithOldCredentialsFails = $this->runPostRequest(
            '/change-password',
            null,
            [
                'currentPassword' => '1234',
                'newPassword' => 'abcd',
                'uniqueUserIdentifier' => 'this-email-does-not-exist@localhost.dev',
            ]
        );
        $this->assertIsProblemResponse($changePasswordWithOldCredentialsFails, 400);
    }

    public function testChangePasswordFailsIfTypeIsWrong(): void
    {
        $changePasswordWithOldCredentialsFails = $this->runPostRequest(
            '/change-password',
            null,
            [
                'type' => 'NotActionChangePassword',
                'currentPassword' => '1234',
                'newPassword' => 'abcd',
                'uniqueUserIdentifier' => 'this-email-does-not-exist@localhost.dev',
            ]
        );
        $this->assertIsProblemResponse($changePasswordWithOldCredentialsFails, 400);
    }

    public function testChangePasswordFailsIfCurrentPasswordIsMissing(): void
    {
        $changePasswordWithOldCredentialsFails = $this->runPostRequest(
            '/change-password',
            null,
            [
                'type' => 'ActionChangePassword',
                'newPassword' => 'abcd',
                'uniqueUserIdentifier' => 'this-email-does-not-exist@localhost.dev',
            ]
        );
        $this->assertIsProblemResponse($changePasswordWithOldCredentialsFails, 400);
    }

    public function testChangePasswordFailsIfNewPasswordIsMissing(): void
    {
        $changePasswordWithOldCredentialsFails = $this->runPostRequest(
            '/change-password',
            null,
            [
                'type' => 'ActionChangePassword',
                'currentPassword' => '1234',
                'uniqueUserIdentifier' => 'this-email-does-not-exist@localhost.dev',
            ]
        );
        $this->assertIsProblemResponse($changePasswordWithOldCredentialsFails, 400);
    }

    public function testChangePasswordFailsIfUniqueUserIdentifierIsMissing(): void
    {
        $changePasswordWithOldCredentialsFails = $this->runPostRequest(
            '/change-password',
            null,
            [
                'type' => 'ActionChangePassword',
                'currentPassword' => '1234',
                'newPassword' => 'abcd',
            ]
        );
        $this->assertIsProblemResponse($changePasswordWithOldCredentialsFails, 400);
    }

    public function testChangePasswordFailsIfNewPasswordIsIdenticalToCurrentPassword(): void
    {
        $changePasswordWithOldCredentialsFails = $this->runPostRequest(
            '/change-password',
            null,
            [
                'type' => 'ActionChangePassword',
                'currentPassword' => '1234',
                'newPassword' => '1234',
                'uniqueUserIdentifier' => 'this-email-does-not-exist@localhost.dev',
            ]
        );
        $this->assertIsProblemResponse($changePasswordWithOldCredentialsFails, 400);
    }
}
