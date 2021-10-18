<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/UserGroup/AttachUserControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\UserGroup;

use App\DataFixtures\ORM\LoadUserData;
use App\DataFixtures\ORM\LoadUserGroupData;
use App\Utils\JSON;
use App\Utils\Tests\PhpUnitUtil;
use App\Utils\Tests\WebTestCase;
use Generator;
use Throwable;

/**
 * Class AttachUserControllerTest
 *
 * @package App\Tests\E2E\Controller\v1\UserGroup
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class AttachUserControllerTest extends WebTestCase
{
    private string $baseUrl = '/v1/user_group';

    /**
     * @throws Throwable
     */
    public static function tearDownAfterClass(): void
    {
        self::bootKernel();

        PhpUnitUtil::loadFixtures(self::$kernel);

        self::$kernel->shutdown();

        parent::tearDownAfterClass();
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `POST /v1/user_group/{user}/user/{userGroup}` returns 401 for non-logged in user
     */
    public function testThatAttachUserGroupReturns401(): void
    {
        $groupUuid = LoadUserGroupData::$uuids['Role-root'];
        $userUuid = LoadUserData::$uuids['john'];

        $client = $this->getTestClient();
        $client->request('POST', $this->baseUrl . '/' . $groupUuid . '/user/' . $userUuid);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(401, $response->getStatusCode(), $content . "\nResponse:\n" . $response);
    }

    /**
     * @dataProvider dataProviderTestThatAttachUserActionReturns403ForInvalidUser
     *
     * @throws Throwable
     *
     * @testdox Test that `POST /v1/user_group/{user}/user/{userGroup}` returns 403 for $u + $p, who hasn't `ROLE_ROOT`
     */
    public function testThatAttachUserActionReturns403ForInvalidUser(string $u, string $p): void
    {
        $groupUuid = LoadUserGroupData::$uuids['Role-root'];
        $userUuid = LoadUserData::$uuids['john'];

        $client = $this->getTestClient($u, $p);
        $client->request('POST', $this->baseUrl . '/' . $groupUuid . '/user/' . $userUuid);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(403, $response->getStatusCode(), "Response:\n" . $response);
        self::assertJsonStringEqualsJsonString(
            '{"message":"Access denied.","code":0,"status":403}',
            $content,
            "Response:\n" . $response
        );
    }

    /**
     * @dataProvider dataProviderTestThatAttachUserActionWorksAsExpected
     *
     * @throws Throwable
     *
     * @testdox Test that `POST /v1/user_group/{user}/user/{userGroup}` returns status $expectedStatus with root user
     */
    public function testThatAttachUserActionWorksAsExpected(int $expectedStatus): void
    {
        $groupUuid = LoadUserGroupData::$uuids['Role-root'];
        $userUuid = LoadUserData::$uuids['john'];

        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('POST', $this->baseUrl . '/' . $groupUuid . '/user/' . $userUuid);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame($expectedStatus, $response->getStatusCode(), "Response:\n" . $response);
        self::assertCount(2, JSON::decode($content));
    }

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public function dataProviderTestThatAttachUserActionReturns403ForInvalidUser(): Generator
    {
        yield ['john', 'password'];
        yield ['john-api', 'password-api'];
        yield ['john-logged', 'password-logged'];
        yield ['john-user', 'password-user'];
        yield ['john-admin', 'password-admin'];
    }

    /**
     * @return Generator<array{0: int}>
     */
    public function dataProviderTestThatAttachUserActionWorksAsExpected(): Generator
    {
        yield [201];
        yield [200];
    }
}
