<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/User/UserRolesControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\User;

use App\DataFixtures\ORM\LoadUserData;
use App\Security\RolesService;
use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Generator;
use Throwable;

/**
 * Class UserRolesControllerTest
 *
 * @package App\Tests\E2E\Controller\v1\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserRolesControllerTest extends WebTestCase
{
    private string $baseUrl = '/v1/user';

    /**
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/user/{userUuid}/roles` returns 401 for non-logged in user
     */
    public function testThatGetUserRolesReturnsReturns401(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', $this->baseUrl . '/' . LoadUserData::$uuids['john-user'] . '/roles');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(401, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatGetRolesActionsReturns403ForInvalidUser
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/user/$userId/roles` returns 403 with invalid user $username + $password
     */
    public function testThatGetUserRolesReturns403ForInvalidUser(
        string $userId,
        string $username,
        string $password
    ): void {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl . '/' . $userId . '/roles');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(403, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatGetUserRolesReturns200ForUserHimself
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/user/$userId/roles` returns expected for user him/herself with $username + $password
     */
    public function testThatGetUserRolesReturns200ForUserHimself(
        string $userId,
        string $username,
        string $password,
        string $expectedResponse
    ): void {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl . '/' . $userId . '/roles');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(200, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
        self::assertJsonStringEqualsJsonString($expectedResponse, $content);
    }

    /**
     * @dataProvider dataProviderTestThatGetRolesReturns200ForRootRoleUser
     *
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/user/$userId/roles` returns expected `$expectedResponse` for user who has `ROLE_ROOT`
     */
    public function testThatGetUserRolesReturns200ForRootRoleUser(string $userId, string $expectedResponse): void
    {
        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('GET', $this->baseUrl . '/' . $userId . '/roles');

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(200, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
        self::assertJsonStringEqualsJsonString($expectedResponse, $content);
    }

    /**
     * @return Generator<array{0: string, 1: string, 2: string}>
     */
    public function dataProviderTestThatGetRolesActionsReturns403ForInvalidUser(): Generator
    {
        yield [LoadUserData::$uuids['john-api'], 'john', 'password'];
        yield [LoadUserData::$uuids['john-logged'], 'john-api', 'password-api'];
        yield [LoadUserData::$uuids['john-user'], 'john-logged', 'password-logged'];
        yield [LoadUserData::$uuids['john-admin'], 'john-user', 'password-user'];
        yield [LoadUserData::$uuids['john'], 'john-admin', 'password-admin'];
    }

    /**
     * @throws Throwable
     *
     * @return Generator<array{0: string, 1: string, 2: string, 3: string}>
     */
    public function dataProviderTestThatGetUserRolesReturns200ForUserHimself(): Generator
    {
        $rolesService = self::getContainer()->get(RolesService::class);

        yield [
            LoadUserData::$uuids['john'],
            'john',
            'password',
            '[]',
        ];

        yield [
            LoadUserData::$uuids['john-api'],
            'john-api',
            'password-api',
            JSON::encode($rolesService->getInheritedRoles([RolesService::ROLE_API])),
        ];

        yield [
            LoadUserData::$uuids['john-logged'],
            'john-logged',
            'password-logged',
            JSON::encode($rolesService->getInheritedRoles([RolesService::ROLE_LOGGED])),
        ];

        yield [
            LoadUserData::$uuids['john-user'],
            'john-user',
            'password-user',
            JSON::encode($rolesService->getInheritedRoles([RolesService::ROLE_USER])),
        ];

        yield [
            LoadUserData::$uuids['john-admin'],
            'john-admin',
            'password-admin',
            JSON::encode($rolesService->getInheritedRoles([RolesService::ROLE_ADMIN])),
        ];

        yield [
            LoadUserData::$uuids['john-root'],
            'john-root',
            'password-root',
            JSON::encode($rolesService->getInheritedRoles([RolesService::ROLE_ROOT])),
        ];
    }

    /**
     * @throws Throwable
     *
     * @return Generator<array{0: string, 1: string}>
     */
    public function dataProviderTestThatGetRolesReturns200ForRootRoleUser(): Generator
    {
        $rolesService = self::getContainer()->get(RolesService::class);

        yield [LoadUserData::$uuids['john'], '[]'];

        yield [
            LoadUserData::$uuids['john-api'],
            JSON::encode($rolesService->getInheritedRoles([RolesService::ROLE_API])),
        ];

        yield [
            LoadUserData::$uuids['john-logged'],
            JSON::encode($rolesService->getInheritedRoles([RolesService::ROLE_LOGGED])),
        ];

        yield [
            LoadUserData::$uuids['john-user'],
            JSON::encode($rolesService->getInheritedRoles([RolesService::ROLE_USER])),
        ];

        yield [
            LoadUserData::$uuids['john-admin'],
            JSON::encode($rolesService->getInheritedRoles([RolesService::ROLE_ADMIN])),
        ];

        yield [
            LoadUserData::$uuids['john-root'],
            JSON::encode($rolesService->getInheritedRoles([RolesService::ROLE_ROOT])),
        ];
    }
}
