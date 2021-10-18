<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/Profile/GroupsControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\Profile;

use App\Security\RolesService;
use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Generator;
use JsonException;
use stdClass;
use Throwable;
use function array_map;

/**
 * Class GroupsControllerTest
 *
 * @package App\Tests\E2E\Controller\v1\Profile
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class GroupsControllerTest extends WebTestCase
{
    private string $baseUrl = '/v1/profile/groups';

    /**
     * @throws Throwable
     *
     * @testdox Test that `GET /v1/profile/groups` returns 401 without Json Web Token
     */
    public function testThatGroupsActionReturns401WithoutToken(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(401, $response->getStatusCode(), "Response:\n" . $response);

        $responseContent = JSON::decode($content);

        $info = "\nResponse:\n" . $response;

        self::assertObjectHasAttribute('code', $responseContent, 'Response does not contain "code"' . $info);
        self::assertSame(401, $responseContent->code, 'Response code was not expected' . $info);
        self::assertObjectHasAttribute('message', $responseContent, 'Response does not contain "message"' . $info);
        self::assertSame(
            'JWT Token not found',
            $responseContent->message,
            'Response message was not expected' . $info,
        );
    }

    /**
     * @throws JsonException
     *
     * @testdox Test that `GET /v1/profile/groups` returns 401 with invalid ApiKey token
     */
    public function testThatGroupsActionReturns401WithInvalidApiKey(): void
    {
        $client = $this->getApiKeyClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(401, $response->getStatusCode(), "Response:\n" . $response);

        $responseContent = JSON::decode($content);

        $info = "\nResponse:\n" . $response;

        self::assertObjectHasAttribute('code', $responseContent, 'Response does not contain "code"' . $info);
        self::assertSame(401, $responseContent->code, 'Response code was not expected' . $info);
        self::assertObjectHasAttribute('message', $responseContent, 'Response does not contain "message"' . $info);
        self::assertSame(
            'JWT Token not found',
            $responseContent->message,
            'Response message was not expected' . $info,
        );
    }

    /**
     * @dataProvider dataProviderTestThatGroupsActionReturnExpected
     *
     * @param array<int, string> $expected
     *
     * @throws Throwable
     *
     * @testdox Test that ``GET /v1/profile/groups` returns expected groups with $username + $password
     */
    public function testThatGroupsActionReturnExpected(string $username, string $password, array $expected): void
    {
        $client = $this->getTestClient($username, $password);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(200, $response->getStatusCode(), "Response:\n" . $response);

        $responseContent = JSON::decode($content);

        if (empty($expected)) {
            self::assertEmpty($responseContent);
        } else {
            $iterator = static function (stdClass $userGroup): string {
                return $userGroup->role->id;
            };

            self::assertSame($expected, array_map($iterator, $responseContent));
        }
    }

    /**
     * @dataProvider dataProviderTestThatGroupsActionReturnExpectedWithValidApiKey
     *
     * @throws JsonException
     *
     * @testdox Test that `GET /v1/profile/groups` returns expected with invalid $token token
     */
    public function testThatGroupsActionReturnExpectedWithValidApiKey(string $token): void
    {
        $client = $this->getApiKeyClient($token);
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(401, $response->getStatusCode(), "Response:\n" . $response);

        $responseContent = JSON::decode($content);

        $info = "\nResponse:\n" . $response;

        self::assertObjectHasAttribute('code', $responseContent, 'Response does not contain "code"' . $info);
        self::assertSame(401, $responseContent->code, 'Response code was not expected' . $info);
        self::assertObjectHasAttribute('message', $responseContent, 'Response does not contain "message"' . $info);
        self::assertSame(
            'JWT Token not found',
            $responseContent->message,
            'Response message was not expected' . $info,
        );
    }

    /**
     * @return Generator<array{0: string, 1: string, 2: array<int, string>}>
     */
    public function dataProviderTestThatGroupsActionReturnExpected(): Generator
    {
        yield ['john', 'password', []];
        yield ['john-logged', 'password-logged', ['ROLE_LOGGED']];
        yield ['john-user', 'password-user', ['ROLE_USER']];
        yield ['john-admin', 'password-admin', ['ROLE_ADMIN']];
        yield ['john-root', 'password-root', ['ROLE_ROOT']];
        yield ['john.doe@test.com', 'password', []];
        yield ['john.doe-logged@test.com', 'password-logged', ['ROLE_LOGGED']];
        yield ['john.doe-user@test.com', 'password-user', ['ROLE_USER']];
        yield ['john.doe-admin@test.com', 'password-admin', ['ROLE_ADMIN']];
        yield ['john.doe-root@test.com', 'password-root', ['ROLE_ROOT']];
    }

    /**
     * @return Generator<array{0: string}>
     */
    public function dataProviderTestThatGroupsActionReturnExpectedWithValidApiKey(): Generator
    {
        $rolesService = self::getContainer()->get(RolesService::class);

        foreach ($rolesService->getRoles() as $role) {
            yield [str_pad($rolesService->getShort($role), 40, '_')];
        }
    }
}
